<?php
// app/Http/Controllers/Api/AuditApiController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditSession;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditApiController extends Controller
{
    private $auditService;
    
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get audit sessions for API
     */
    public function getSessions(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status');
        $limit = $request->get('limit', 10);
        
        $query = AuditSession::with(['auditedUser', 'auditor'])
            ->where('auditor_id', $user->id);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $sessions = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $sessions,
            'total' => $sessions->count()
        ]);
    }

    /**
     * Get session details
     */
    public function getSessionDetails($sessionId)
    {
        $session = AuditSession::with(['auditedUser', 'auditor', 'auditLogs'])
            ->findOrFail($sessionId);
        
        // Check authorization
        if ($session->auditor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    /**
     * Get audit statistics for API
     */
    public function getStatistics(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'monthly');
        
        $stats = $this->auditService->getAuditStatistics($user->id, $period);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Create new audit session via API
     */
    public function createSession(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'jenis_audit' => 'required|in:quarterly,annual,promotion,disciplinary'
        ]);
        
        try {
            $session = $this->auditService->startAuditSession(
                Auth::id(),
                $request->employee_id,
                $request->jenis_audit
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Audit session created successfully',
                'data' => $session
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Complete session via API
     */
    public function completeSession(Request $request, $sessionId)
    {
        $request->validate([
            'auditor_notes' => 'nullable|string|max:1000'
        ]);
        
        try {
            $result = $this->auditService->completeAuditSession(
                $sessionId,
                $request->auditor_notes
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Audit session completed successfully',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}