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
            return redirect()->route('audit.interview', $existingSession->session_code)
                           ->with('warning', 'There is already an active audit session for this employee.');
        }
        
        // Create new audit session
        $sessionId = Str::uuid();
        
        $auditSession = AuditSession::create([
            'audited_user_id' => $employee->id,
            'auditor_id' => $user->id,
            'cabang_id' => $employee->cabang_id,
            'session_code' => $sessionId,
            'status' => 'pending',
            'total_questions' => 0,
            'answered_questions' => 0,
        ]);
        
        return redirect()->route('audit.interview',$auditSession->session_code);
    }
    
    public function interview($sessionCode)
    {
        $auditSession = AuditSession::with(['employee', 'auditor'])->where('session_code', $sessionCode)->firstOrFail();

        $answered = $auditSession->answered_questions ?? 0;
        $total = 5; // Misalnya 5 pertanyaan total

        if ($answered >= $total) {
            return view('audit.interview', compact('auditSession'))->with('currentQuestion', null);
        }

        $categories = ['leadership', 'teamwork', 'recruitment', 'effectiveness', 'innovation'];
        $kategori = $categories[$answered % count($categories)];

        $questionNumber = $answered + 1;

        $openai = new OpenAIService();
        $pertanyaan = $openai->generateAuditQuestion($auditSession->employee->role->name ?? 'Karyawan', $kategori, $questionNumber);

        $currentQuestion = [
            'question_number' => $questionNumber,
            'kategori' => $kategori,
            'pertanyaan' => $pertanyaan,
        ];

        return view('audit.interview', compact('auditSession', 'currentQuestion'));
    }


    
    public function beginInterview(Request $request, $sessionId)
    {
        $auditSession = AuditSession::where('session_code', $sessionId)->firstOrFail();
        
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
            'session_code' => $sessionId,
        ]);
    }

    public function submitAnswer(Request $request, $sessionCode)
    {
        $request->validate([
            'jawaban' => 'required|string|max:1000',
        ]);

        $session = AuditSession::where('session_code', $sessionCode)->firstOrFail();

        $openai = new OpenAIService();

        // Tentukan kategori dan nomor pertanyaan saat ini
        $categories = ['leadership', 'teamwork', 'recruitment', 'effectiveness', 'innovation'];
        $totalAnswered = $session->answered_questions ?? 0;
        $questionNumber = $totalAnswered + 1;

        $kategori = $categories[intval(floor(($questionNumber - 1) / 1)) % count($categories)];

        $pertanyaan = $openai->generateAuditQuestion($session->employee->role->name ?? 'Karyawan', $kategori, $questionNumber);

        // Kirim ke GPT untuk analisa
        $analysis = $openai->analyzeAnswer($pertanyaan, $request->jawaban, $kategori, $session->employee->role->name ?? 'Karyawan');

        // Simpan ke audit_logs
        AuditLog::create([
            'session_code' => $session->session_code,
            'question_number' => $questionNumber,
            'kategori' => $kategori,
            'pertanyaan' => $pertanyaan,
            'jawaban' => $request->jawaban,
            'ai_sentiment' => json_encode($analysis['sentiment']),
            'skor_jawaban' => $analysis['skor'],
            'ai_feedback' => $analysis['feedback'],
            'answered_at' => now(),
        ]);

        // Update session progress
        $session->answered_questions += 1;
        $session->save();

        return redirect()->route('audit.interview', $session->session_code);
    }

    
    public function completeAudit(Request $request, $sessionId)
    {
        $auditSession = AuditSession::where('session_code', $sessionId)->firstOrFail();

        if (Auth::id() !== $auditSession->auditor_id) {
            abort(403, 'Unauthorized access to audit session.');
        }

        DB::transaction(function () use ($auditSession) {
            // Ambil semua log audit
            $logs = $auditSession->auditLogs()->get()->map(function ($log) {
                return [
                    'pertanyaan' => $log->pertanyaan,
                    'jawaban' => $log->jawaban,
                    'skor_jawaban' => $log->skor_jawaban,
                ];
            })->toArray();

            // Hitung skor per kategori
            $skor = [
                'leadership' => round($auditSession->auditLogs()->where('kategori', 'leadership')->avg('skor_jawaban'), 2),
                'teamwork' => round($auditSession->auditLogs()->where('kategori', 'teamwork')->avg('skor_jawaban'), 2),
                'recruitment' => round($auditSession->auditLogs()->where('kategori', 'recruitment')->avg('skor_jawaban'), 2),
                'effectiveness' => round($auditSession->auditLogs()->where('kategori', 'effectiveness')->avg('skor_jawaban'), 2),
                'innovation' => round($auditSession->auditLogs()->where('kategori', 'innovation')->avg('skor_jawaban'), 2),
            ];
            $skor['total'] = round(array_sum($skor) / count($skor), 2);

            // Informasi user
            $userInfo = [
                'name' => $auditSession->employee->name,
                'role' => $auditSession->employee->role,
                'cabang' => $auditSession->employee->cabang->nama ?? '-'
            ];

            // Panggil GPT summary
            $summary = $this->openAIService->generateFinalSummary($logs, $skor, $userInfo);

            // Simpan ke audit_session
            $auditSession->update([
                'status' => 'completed',
                'completed_at' => now(),
                'skor_leadership' => $skor['leadership'],
                'skor_teamwork' => $skor['teamwork'],
                'skor_recruitment' => $skor['recruitment'],
                'skor_effectiveness' => $skor['effectiveness'],
                'skor_innovation' => $skor['innovation'],
                'skor_total' => $skor['total'],
                'catatan_ai' => $summary,
                'rekomendasi_ai' => $summary['rekomendasi'] ?? null,
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
        $auditSession = AuditSession::where('session_code', $sessionId)
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
        
        $auditSession = AuditSession::where('session_code', $sessionId)->firstOrFail();
        
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
        $auditSession = AuditSession::where('session_code', $sessionId)
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

    public function finish($sessionId)
    {
        $session = AuditSession::where('session_code', $sessionId)->firstOrFail();

        if ($session->status !== 'completed') {
            $session->update(['status' => 'completed']);
        }

        return redirect()->route('audit.result', $session->session_code)
            ->with('success', 'Audit berhasil diselesaikan.');
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