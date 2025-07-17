<?php

namespace App\Services;

use App\Models\AuditSession;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Jabatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditService
{
    private $openAIService;
    
    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Start new audit session
     */
    public function startAuditSession($auditorId, $auditedUserId, $jenisAudit = 'quarterly')
    {
        try {
            DB::beginTransaction();
            
            $auditor = User::find($auditorId);
            $auditedUser = User::find($auditedUserId);
            
            // Validasi apakah auditor bisa mengaudit user ini
            if (!$auditor->canAudit($auditedUser)) {
                throw new \Exception('Anda tidak memiliki akses untuk mengaudit karyawan ini.');
            }
            
            // Cek apakah ada audit session yang masih aktif
            $activeSession = AuditSession::where('auditor_id', $auditorId)
                ->where('audited_user_id', $auditedUserId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->first();
                
            if ($activeSession) {
                throw new \Exception('Masih ada sesi audit aktif untuk karyawan ini.');
            }
            
            $session = AuditSession::create([
                'auditor_id' => $auditorId,
                'audited_user_id' => $auditedUserId,
                'jenis_audit' => $jenisAudit,
                'status' => 'pending',
            ]);
            
            DB::commit();
            
            return $session;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error starting audit session: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Begin audit interview process
     */
    public function beginInterview($sessionId)
    {
        try {
            $session = AuditSession::find($sessionId);
            
            if (!$session) {
                throw new \Exception('Sesi audit tidak ditemukan.');
            }
            
            if ($session->status !== 'pending') {
                throw new \Exception('Sesi audit tidak dalam status yang tepat untuk dimulai.');
            }
            
            $session->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
            
            return $session;
            
        } catch (\Exception $e) {
            Log::error('Error beginning interview: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get next question for audit
     */
    public function getNextQuestion($sessionId, $kategori, $questionNumber)
    {
        try {
            $session = AuditSession::with('auditedUser.jabatan')->find($sessionId);
            
            if (!$session) {
                throw new \Exception('Sesi audit tidak ditemukan.');
            }
            
            $jabatan = $session->auditedUser->role;
            
            // Generate question using OpenAI
            $question = $this->openAIService->generateAuditQuestion($jabatan, $kategori, $questionNumber);
            
            return [
                'question' => $question,
                'kategori' => $kategori,
                'question_number' => $questionNumber,
                'session_code' => $sessionId
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting next question: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process answer and save to audit log
     */
    public function processAnswer($sessionId, $questionNumber, $kategori, $question, $answer)
    {
        try {
            DB::beginTransaction();
            
            $session = AuditSession::with('auditedUser.jabatan')->find($sessionId);
            
            if (!$session) {
                throw new \Exception('Sesi audit tidak ditemukan.');
            }
            
            $jabatan = $session->auditedUser->role;
            
            // Analyze answer using OpenAI
            $analysis = $this->openAIService->analyzeAnswer($question, $answer, $kategori, $jabatan);
            
            // Save to audit log
            $auditLog = AuditLog::create([
                'session_code' => $sessionId,
                'question_number' => $questionNumber,
                'kategori' => $kategori,
                'pertanyaan' => $question,
                'jawaban' => $answer,
                'ai_sentiment' => $analysis['sentiment'],
                'skor_jawaban' => $analysis['skor'],
                'ai_feedback' => $analysis['feedback'],
                'answered_at' => now(),
            ]);
            
            DB::commit();
            
            return [
                'audit_log' => $auditLog,
                'analysis' => $analysis,
                'feedback' => $analysis['feedback'],
                'score' => $analysis['skor']
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing answer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Complete audit session and generate final report
     */
    public function completeAuditSession($sessionId, $auditorNotes = null)
    {
        try {
            DB::beginTransaction();
            
            $session = AuditSession::with(['auditedUser', 'auditLogs'])->find($sessionId);
            
            if (!$session) {
                throw new \Exception('Sesi audit tidak ditemukan.');
            }
            
            // Calculate category scores
            $scores = $this->calculateCategoryScores($session->auditLogs);
            
            // Generate final AI summary
            $userInfo = [
                'name' => $session->auditedUser->name,
                'role' => $session->auditedUser->role,
                'cabang' => $session->auditedUser->cabang->nama_cabang ?? 'N/A'
            ];
            
            $aiSummary = $this->openAIService->generateFinalSummary(
                $session->auditLogs->toArray(),
                $scores,
                $userInfo
            );
            
            // Update session with final results
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'skor_leadership' => $scores['leadership'],
                'skor_teamwork' => $scores['teamwork'],
                'skor_recruitment' => $scores['recruitment'],
                'skor_effectiveness' => $scores['effectiveness'],
                'skor_innovation' => $scores['innovation'],
                'skor_total' => $scores['total'],
                'ai_analysis' => $aiSummary,
                'rekomendasi_ai' => $aiSummary['rekomendasi'],
                'catatan_ai' => $aiSummary['ringkasan'],
                'keputusan_final' => $aiSummary['rekomendasi'],
                'catatan_auditor' => $auditorNotes,
                'is_overridden' => false,
            ]);
            
            DB::commit();
            
            return [
                'session' => $session->fresh(),
                'scores' => $scores,
                'ai_summary' => $aiSummary
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completing audit session: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate category scores from audit logs
     */
    private function calculateCategoryScores($auditLogs)
    {
        $categories = ['leadership', 'teamwork', 'recruitment', 'effectiveness', 'innovation'];
        $scores = [];
        
        foreach ($categories as $category) {
            $categoryLogs = $auditLogs->where('kategori', $category);
            
            if ($categoryLogs->count() > 0) {
                $scores[$category] = round($categoryLogs->avg('skor_jawaban'), 2);
            } else {
                $scores[$category] = 0;
            }
        }
        
        $scores['total'] = round(array_sum($scores) / count($categories), 2);
        
        return $scores;
    }

    /**
     * Override AI recommendation (for auditor)
     */
    public function overrideRecommendation($sessionId, $newRecommendation, $auditorNotes)
    {
        try {
            $session = AuditSession::find($sessionId);
            
            if (!$session) {
                throw new \Exception('Sesi audit tidak ditemukan.');
            }
            
            $session->update([
                'keputusan_final' => $newRecommendation,
                'catatan_auditor' => $auditorNotes,
                'is_overridden' => true,
            ]);
            
            return $session;
            
        } catch (\Exception $e) {
            Log::error('Error overriding recommendation: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get audit statistics for dashboard
     */
    public function getAuditStatistics($auditorId, $period = 'monthly')
    {
        try {
            $startDate = match($period) {
                'weekly' => now()->subWeek(),
                'monthly' => now()->subMonth(),
                'quarterly' => now()->subQuarter(),
                'yearly' => now()->subYear(),
                default => now()->subMonth(),
            };
            
            $sessions = AuditSession::where('auditor_id', $auditorId)
                ->where('created_at', '>=', $startDate)
                ->get();
            
            $stats = [
                'total_audits' => $sessions->count(),
                'completed_audits' => $sessions->where('status', 'completed')->count(),
                'pending_audits' => $sessions->where('status', 'pending')->count(),
                'in_progress_audits' => $sessions->where('status', 'in_progress')->count(),
                'recommendations' => [
                    'promosi' => $sessions->where('rekomendasi_ai', 'PROMOSI')->count(),
                    'tetap' => $sessions->where('rekomendasi_ai', 'TETAP')->count(),
                    'demosi' => $sessions->where('rekomendasi_ai', 'DEMOSI')->count(),
                ],
                'average_score' => round($sessions->where('status', 'completed')->avg('skor_total'), 2),
                'override_rate' => $sessions->where('is_overridden', true)->count() / max($sessions->count(), 1) * 100,
            ];
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Error getting audit statistics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit questions template based on position
     */
    public function getAuditQuestionsTemplate($jabatan)
    {
        return [
            'leadership' => [
                'Bagaimana cara Anda memimpin tim untuk mencapai target penjualan?',
                'Ceritakan tentang situasi sulit yang pernah Anda hadapi sebagai leader.',
                'Bagaimana Anda memotivasi tim yang sedang mengalami penurunan performa?'
            ],
            'teamwork' => [
                'Bagaimana Anda berkolaborasi dengan tim untuk mencapai tujuan bersama?',
                'Ceritakan pengalaman mengatasi konflik dalam tim.',
                'Bagaimana cara Anda berbagi knowledge dengan rekan kerja?'
            ],
            'recruitment' => [
                'Bagaimana strategi Anda dalam merekrut nasabah baru?',
                'Apa yang menjadi key success factor dalam closing deal?',
                'Bagaimana Anda mengatasi nasabah yang ragu untuk berinvestasi?'
            ],
            'effectiveness' => [
                'Bagaimana Anda mengelola waktu untuk mencapai target optimal?',
                'Ceritakan strategi peningkatan produktivitas yang pernah Anda lakukan.',
                'Bagaimana cara Anda mengukur keberhasilan pekerjaan?'
            ],
            'innovation' => [
                'Ide inovatif apa yang pernah Anda implementasikan?',
                'Bagaimana Anda beradaptasi dengan perubahan kondisi pasar?',
                'Apa rencana pengembangan bisnis Anda ke depan?'
            ]
        ];
    }
}