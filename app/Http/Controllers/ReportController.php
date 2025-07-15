<?php

// app/Http/Controllers/ReportController.php
namespace App\Http\Controllers;

use App\Models\AuditSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status');
        
        $query = AuditSession::with(['auditedUser', 'auditor'])
            ->where('auditor_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $sessions = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Statistics
        $stats = [
            'total' => $sessions->total(),
            'completed' => AuditSession::where('auditor_id', $user->id)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'pending' => AuditSession::where('auditor_id', $user->id)
                ->where('status', 'pending')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'in_progress' => AuditSession::where('auditor_id', $user->id)
                ->where('status', 'in_progress')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
        ];
        
        return view('reports.index', compact('sessions', 'stats', 'startDate', 'endDate', 'status'));
    }

    public function analytics(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'monthly');
        
        $startDate = match($period) {
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            'quarterly' => now()->subQuarter(),
            'yearly' => now()->subYear(),
            default => now()->subMonth(),
        };
        
        // Performance trends
        $performanceTrends = AuditSession::where('auditor_id', $user->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(skor_total) as avg_score'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Category performance
        $categoryPerformance = AuditSession::where('auditor_id', $user->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('AVG(skor_leadership) as leadership'),
                DB::raw('AVG(skor_teamwork) as teamwork'),
                DB::raw('AVG(skor_recruitment) as recruitment'),
                DB::raw('AVG(skor_effectiveness) as effectiveness'),
                DB::raw('AVG(skor_innovation) as innovation')
            )
            ->first();
        
        // Recommendation distribution
        $recommendations = AuditSession::where('auditor_id', $user->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select('rekomendasi_ai', DB::raw('COUNT(*) as count'))
            ->groupBy('rekomendasi_ai')
            ->get();
        
        return view('reports.analytics', compact(
            'performanceTrends',
            'categoryPerformance',
            'recommendations',
            'period'
        ));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $format = $request->get('format', 'excel');
        
        $sessions = AuditSession::with(['auditedUser', 'auditor'])
            ->where('auditor_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();
        
        if ($format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('reports.pdf', compact('sessions', 'startDate', 'endDate'));
            return $pdf->download('audit-report-' . now()->format('Y-m-d') . '.pdf');
        } else {
            return $this->exportToExcel($sessions, $startDate, $endDate);
        }
    }

    private function exportToExcel($sessions, $startDate, $endDate)
    {
        $filename = 'audit-report-' . now()->format('Y-m-d') . '.xlsx';
        
        return response()->streamDownload(function () use ($sessions) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'Session Code',
                'Employee Name',
                'Employee ID',
                'Position',
                'Branch',
                'Audit Date',
                'Leadership Score',
                'Teamwork Score',
                'Recruitment Score',
                'Effectiveness Score',
                'Innovation Score',
                'Total Score',
                'AI Recommendation',
                'Final Decision',
                'Status'
            ]);
            
            // Data
            foreach ($sessions as $session) {
                fputcsv($file, [
                    $session->session_code,
                    $session->auditedUser->name,
                    $session->auditedUser->employee_id,
                    $session->auditedUser->role,
                    $session->auditedUser->cabang->nama_cabang ?? 'N/A',
                    $session->created_at->format('Y-m-d'),
                    $session->skor_leadership,
                    $session->skor_teamwork,
                    $session->skor_recruitment,
                    $session->skor_effectiveness,
                    $session->skor_innovation,
                    $session->skor_total,
                    $session->rekomendasi_ai,
                    $session->keputusan_final,
                    $session->status
                ]);
            }
            
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}