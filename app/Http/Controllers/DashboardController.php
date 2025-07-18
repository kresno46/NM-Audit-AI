<?php

// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\AuditSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get statistics based on user role
        $stats = $this->getDashboardStats($user);
        
        // Get recent audit sessions
        $recentAudits = $this->getRecentAudits($user);
        
        // Get pending audits
        $pendingAudits = $this->getPendingAudits($user);
        
        // Get chart data
        $chartData = $this->getChartData($user);

        // Get upcoming audits
        $upcomingAudits = $this->getUpcomingAudits($user);
        
        $branchPerformance = $this->getBranchPerformance($user);

        return view('dashboard', [
            'stats' => $stats,
            'recent_audits' => $recentAudits,
            'pending_audits' => $pendingAudits,
            'chartData' => $chartData,
            'upcoming_audits' => $upcomingAudits,
            'branch_performance' => $branchPerformance,
        ]);
    }

private function getBranchPerformance($user)
{
    $query = \DB::table('audit_sessions')
        ->join('users', 'audit_sessions.audited_user_id', '=', 'users.id')
        ->join('cabang', 'users.cabang_id', '=', 'cabang.id')
        ->select(
            'cabang.nama_cabang',
            \DB::raw('COUNT(audit_sessions.id) as total_audits'),
            \DB::raw("SUM(CASE WHEN audit_sessions.status = 'completed' THEN 1 ELSE 0 END) as completed_audits"),
            \DB::raw('AVG(audit_sessions.skor_total) as avg_score'),
            \DB::raw("ROUND((SUM(CASE WHEN audit_sessions.status = 'completed' THEN 1 ELSE 0 END) / COUNT(audit_sessions.id)) * 100, 2) as compliance_rate")
        )
        ->groupBy('cabang.id', 'cabang.nama_cabang');

    // Optional: filter untuk Manager
    if ($user->role === 'Manager') {
        $query->where('users.cabang_id', $user->cabang_id);
    }

    return $query->get();
}
    
    private function getUpcomingAudits($user)
    {
        $query = AuditSession::with(['auditedUser'])
            ->where('status', 'in_progress')
            ->whereDate('started_at', '>=', now());

        $this->applyRoleFiltering($query, $user);

        return $query->orderBy('started_at', 'asc')->limit(5)->get();
    }
    
    public function auditHistory(Request $request)
    {
        $user = Auth::user();
        $query = AuditSession::with(['auditedUser', 'auditor', 'cabang']);
        
        // Filter based on user role
        if ($user->role === 'CEO') {
            // CEO can see all audits
        } elseif ($user->role === 'CBO') {
            // CBO can see audits for Manager and below
            $query->whereHas('auditedUser', function ($q) {
                $q->whereIn('role', ['Manager', 'SBC', 'BC', 'Trainee']);
            });
        } elseif ($user->role === 'Manager') {
            // Manager can see audits in their branch
            $query->where('cabang_id', $user->cabang_id);
        } else {
            // SBC, BC can see audits they conducted or audits of their subordinates
            $query->where(function ($q) use ($user) {
                $q->where('auditor_id', $user->id)
                  ->orWhereHas('auditedUser', function ($subQ) use ($user) {
                      $subQ->where('atasan_id', $user->id);
                  });
            });
        }
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }
        
        if ($request->filled('employee')) {
            $query->where('audited_user_id', $request->employee);
        }
        
        $audits = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get filter options
        $employees = $user->getAuditableEmployees();
        
        return view('audit.history', compact('audits', 'employees'));
    }
    
    private function getDashboardStats($user)
    {
        $query = AuditSession::query();
        
        // Apply role-based filtering
        $this->applyRoleFiltering($query, $user);
        
        $totalAudits = $query->count();
        $completedAudits = $query->where('status', 'completed')->count();
        $inProgressAudits = $query->where('status', 'in_progress')->count();
        $avgScore = $query->where('status', 'completed')->avg('skor_total') ?? 0;
        
        // This month stats
        $thisMonth = $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
        
        $thisMonthAudits = $thisMonth->count();
        $thisMonthCompleted = $thisMonth->where('status', 'completed')->count();
        
        return [
            'total_audits' => $totalAudits,
            'completed_audits' => $completedAudits,
            'in_progress_audits' => $inProgressAudits,
            'avg_score' => round($avgScore, 2),
            'this_month_audits' => $thisMonthAudits,
            'this_month_completed' => $thisMonthCompleted,
            'completion_rate' => $totalAudits > 0 ? round(($completedAudits / $totalAudits) * 100, 2) : 0,
        ];
    }
    
    private function getRecentAudits($user)
    {
        $query = AuditSession::with(['auditedUser', 'auditor', 'cabang']);

        $this->applyRoleFiltering($query, $user);
        
        return $query->orderBy('created_at', 'desc')->limit(5)->get();
    }
    
    private function getPendingAudits($user)
    {
        $query = AuditSession::with(['auditedUser', 'auditor', 'cabang']);
        $this->applyRoleFiltering($query, $user);
        
        return $query->orderBy('created_at', 'desc')->limit(5)->get();
    }
    
    private function getChartData($user)
    {
        $query = AuditSession::query();
        $this->applyRoleFiltering($query, $user);
        
        // Last 6 months data
        $months = [];
        $auditsData = [];
        $completedData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $monthlyAudits = $query->whereMonth('created_at', $date->month)
                                  ->whereYear('created_at', $date->year)
                                  ->count();
            
            $monthlyCompleted = $query->whereMonth('created_at', $date->month)
                                     ->whereYear('created_at', $date->year)
                                     ->where('status', 'completed')
                                     ->count();
            
            $auditsData[] = $monthlyAudits;
            $completedData[] = $monthlyCompleted;
        }
        
        return [
            'months' => $months,
            'audits' => $auditsData,
            'completed' => $completedData,
        ];
    }
    
    private function applyRoleFiltering($query, $user)
    {
        if ($user->role === 'CEO') {
            // CEO can see all
            return;
        } elseif ($user->role === 'CBO') {
            $query->whereHas('auditedUser', function ($q) {
                $q->whereIn('role', ['Manager', 'SBC', 'BC', 'Trainee']);
            });
        } elseif ($user->role === 'Manager') {
            $query->where('cabang_id', $user->cabang_id);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('auditor_id', $user->id)
                  ->orWhereHas('auditedUser', function ($subQ) use ($user) {
                      $subQ->where('atasan_id', $user->id);
                  });
            });
        }
    }
}