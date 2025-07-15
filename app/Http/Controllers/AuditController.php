<?php

// app/Http/Controllers/AuditController.php
namespace App\Http\Controllers;

use App\Models\AuditSession;
use App\Models\User;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class AuditController extends Controller
{
    protected $openAIService;
    
    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }
    
    public function selectEmployee()
{
    $user = Auth::user();
    $employees = $user->getAuditableEmployees();

    // ⬇ Tambahkan baris ini
    $recent_activities = $user->activities()->latest()->take(5)->get();

    // ⬇ Tambahkan ke return view
    return view('audit.select-employee', compact('employees', 'recent_activities'));
}


    
    public function startAudit(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
        ]);
        
        $user = Auth::user();
        $employee = User::findOrFail($request->employee_id);
        
        // Check if user can audit this employee
        if (!$user->canAudit($employee)) {
            return redirect()->back()->with('error', 'You are not authorized to audit this employee.');
        }
        
        // Check if there's already an active audit session
        $existingSession = AuditSession::where('employee_id', $employee->id)
                                      ->where('status', 'in_progress')
                                      ->first();
        
        if ($existingSession) {
            return redirect()->route('audit.interview', $existingSession->session_id)
                           ->with('warning', 'There is already an active audit session for this employee.');
        }
        
        // Create new audit session
        $sessionId = Str::uuid();
        
        $auditSession = AuditSession::create([
            'employee_id' => $employee->id,
            'auditor_id' => $user->id,
            'cabang_id' => $employee->cabang_id,
            'session_id' => $sessionId,
            'status' => 'created',
            'total_questions' => 0,
            'answered_questions' => 0,
        ]);
        
        return redirect()->route('audit.interview', $sessionId);
    }
    
    public function interview($sessionId)
    {
        $auditSession = AuditSession::where('session_id', $sessionId)
                                   ->with(['employee', 'auditor', 'cabang'])
                                   ->firstOrFail();
        
        // Check authorization
        if (Auth::id() !== $auditSession->auditor_id) {
            abort(403, 'Unauthorized access to audit session.');
        }
        
        return view('audit.interview', compact('auditSession'));
    }
    
    public function beginInterview(Request $request, $sessionId)
    {
        $auditSession = AuditSession::where('session_id', $sessionId)->firstOrFail();
        
        // Check authorization
        if (Auth::id() !== $auditSession->auditor_id) {
            abort(403, 'Unauthorized access to audit session.');
        }
        
        // Update session status and start time
        $auditSession->update([
            'status' => 'in_progress',
            'start_time' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Interview started successfully',
            'session_id' => $sessionId,
        ]);
    }
    
    public function completeAudit(Request $request, $sessionId)
    {
        $auditSession = AuditSession::where('session_id', $sessionId)->firstOrFail();
        
        // Check authorization
        if (Auth::id() !== $auditSession->auditor_id) {
            abort(403, 'Unauthorized access to audit session.');
        }
        
        DB::transaction(function () use ($auditSession, $request) {
            // Calculate overall score and recommendation
            $answers = $auditSession->answers()->with('question')->get();
            
            if ($answers->isEmpty()) {
                throw new \Exception('No answers found to complete the audit.');
            }
            
            $totalScore = $answers->sum('score');
            $maxPossibleScore = $answers->sum(function ($answer) {
                return $answer->question->max_score;
            });
            
            $overallScore = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;
            
            // Determine recommendation based on score
            $recommendation = $this->determineRecommendation($overallScore);
            
            // Generate AI summary
            $aiSummary = $this->openAIService->generateAuditSummary($auditSession, $answers);
            
            // Update audit session
            $auditSession->update([
                'status' => 'completed',
                'end_time' => now(),
                'overall_score' => $overallScore,
                'recommendation' => $recommendation,
                'notes' => $aiSummary,
            ]);
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Audit completed successfully',
            'redirect_url' => route('audit.result', $sessionId),
        ]);
    }
    
    public function result($sessionId)
    {
        $auditSession = AuditSession::where('session_id', $sessionId)
                                   ->with(['employee', 'auditor', 'cabang', 'answers.question'])
                                   ->firstOrFail();
        
        // Check authorization
        if (Auth::id() !== $auditSession->auditor_id) {
            abort(403, 'Unauthorized access to audit session.');
        }
        
        // Group answers by category
        $answersByCategory = $auditSession->answers->groupBy('question.category');
        
        return view('audit.result', compact('auditSession', 'answersByCategory'));
    }
    
    public function overrideRecommendation(Request $request, $sessionId)
    {
        $request->validate([
            'recommendation' => 'required|in:Excellent,Good,Average,Poor',
            'override_reason' => 'required|string|max:1000',
        ]);
        
        $auditSession = AuditSession::where('session_id', $sessionId)->firstOrFail();
        
        // Check authorization - only higher level users can override
        $user = Auth::user();
        if (!$this->canOverride($user, $auditSession)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to override this recommendation.',
            ], 403);
        }
        
        $auditSession->update([
            'recommendation' => $request->recommendation,
            'is_overridden' => true,
            'override_reason' => $request->override_reason,
            'override_by' => $user->id,
            'override_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Recommendation overridden successfully',
        ]);
    }
    
    public function exportPdf($sessionId)
    {
        $auditSession = AuditSession::where('session_id', $sessionId)
                                   ->with(['employee', 'auditor', 'cabang', 'answers.question'])
                                   ->firstOrFail();
        
        // Check authorization
        if (Auth::id() !== $auditSession->auditor_id) {
            abort(403, 'Unauthorized access to audit session.');
        }
        
        $answersByCategory = $auditSession->answers->groupBy('question.category');
        
        $pdf = Pdf::loadView('audit.pdf', compact('auditSession', 'answersByCategory'));
        
        $fileName = 'audit_report_' . $auditSession->employee->employee_id . '_' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($fileName);
    }
    
    private function determineRecommendation($score)
    {
        if ($score >= 85) {
            return 'Excellent';
        } elseif ($score >= 70) {
            return 'Good';
        } elseif ($score >= 55) {
            return 'Average';
        } else {
            return 'Poor';
        }
    }
    
    private function canOverride($user, $auditSession)
    {
        // CEO can override any recommendation
        if ($user->role === 'CEO') {
            return true;
        }
        
        // CBO can override recommendations for Manager and below
        if ($user->role === 'CBO' && in_array($auditSession->employee->role, ['Manager', 'SBC', 'BC', 'Trainee'])) {
            return true;
        }
        
        // Manager can override recommendations for SBC and below in their branch
        if ($user->role === 'Manager' && 
            in_array($auditSession->employee->role, ['SBC', 'BC', 'Trainee']) && 
            $user->cabang_id === $auditSession->cabang_id) {
            return true;
        }
        
        return false;
    }
}