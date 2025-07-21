<?php

namespace App\Services;

use App\Models\AuditQuestionTemplate;
use App\Models\AuditResponse;
use App\Models\AuditSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionTemplateService
{
    private $openAIService;
    
    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Get questions for a specific role and category
     */
    public function getQuestionsForRole($role, $category = null, $limit = null)
    {
        $query = AuditQuestionTemplate::active()
            ->forRole($role)
            ->ordered();

        if ($category) {
            $query->forCategory($category);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Generate role-specific questions using ChatGPT
     */
    public function generateRoleSpecificQuestions($role, $categories = [], $countPerCategory = 3)
    {
        $questions = [];
        
        if (empty($categories)) {
            $categories = array_keys(AuditQuestionTemplate::CATEGORIES);
        }

        foreach ($categories as $category) {
            for ($i = 1; $i <= $countPerCategory; $i++) {
                try {
                    $questionText = $this->openAIService->generateRoleSpecificQuestion($role, $category, $i);
                    
                    $questions[] = AuditQuestionTemplate::create([
                        'role' => $role,
                        'category' => $category,
                        'question_text' => $questionText,
                        'question_type' => 'open-ended',
                        'max_score' => 5.00,
                        'difficulty_level' => $this->determineDifficultyLevel($role),
                        'order_index' => $i,
                        'is_active' => true
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error("Error generating question for role {$role}, category {$category}: " . $e->getMessage());
                }
            }
        }

        return $questions;
    }

    /**
     * Process answer with ChatGPT feedback
     */
    public function processAnswerWithFeedback($sessionCode, $questionTemplateId, $answerText, $timeTaken = null)
    {
        try {
            DB::beginTransaction();

            $session = AuditSession::where('session_code', $sessionCode)->firstOrFail();
            $questionTemplate = AuditQuestionTemplate::findOrFail($questionTemplateId);
            
            // Analyze answer with ChatGPT
            $analysis = $this->openAIService->analyzeRoleSpecificAnswer(
                $questionTemplate->question_text,
                $answerText,
                $questionTemplate->category,
                $session->auditedUser->role,
                $questionTemplate->role
            );

            // Create audit response
            $response = AuditResponse::create([
                'audit_session_code' => $sessionCode,
                'audit_question_template_id' => $questionTemplateId,
                'answer_text' => $answerText,
                'score' => $analysis['score'] ?? 3.0,
                'score_percentage' => (($analysis['score'] ?? 3.0) / $questionTemplate->max_score) * 100,
                'feedback' => $analysis['feedback'] ?? 'Jawaban cukup baik.',
                'chatgpt_feedback' => $analysis['detailed_feedback'] ?? null,
                'ai_evaluation' => $analysis,
                'sentiment_analysis' => $analysis['sentiment'] ?? null,
                'key_insights' => $analysis['key_insights'] ?? [],
                'improvement_suggestions' => $analysis['improvement_suggestions'] ?? [],
                'answered_at' => now(),
                'time_taken_seconds' => $timeTaken
            ]);

            // Update session progress
            $this->updateSessionProgress($session);

            DB::commit();

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing answer with feedback: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get role-specific performance benchmarks
     */
    public function getRoleBenchmarks($role)
    {
        $benchmarks = [
            'CEO' => [
                'leadership' => ['min' => 4.0, 'target' => 4.5, 'excellent' => 4.8],
                'teamwork' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5],
                'recruitment' => ['min' => 3.0, 'target' => 3.5, 'excellent' => 4.0],
                'effectiveness' => ['min' => 4.0, 'target' => 4.5, 'excellent' => 4.8],
                'innovation' => ['min' => 4.0, 'target' => 4.5, 'excellent' => 4.8]
            ],
            'CBO' => [
                'leadership' => ['min' => 3.8, 'target' => 4.3, 'excellent' => 4.7],
                'teamwork' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5],
                'recruitment' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5],
                'effectiveness' => ['min' => 3.8, 'target' => 4.3, 'excellent' => 4.7],
                'innovation' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5]
            ],
            'Manager' => [
                'leadership' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5],
                'teamwork' => ['min' => 3.8, 'target' => 4.3, 'excellent' => 4.7],
                'recruitment' => ['min' => 3.8, 'target' => 4.3, 'excellent' => 4.7],
                'effectiveness' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5],
                'innovation' => ['min' => 3.0, 'target' => 3.5, 'excellent' => 4.0]
            ],
            'SBC' => [
                'leadership' => ['min' => 3.0, 'target' => 3.5, 'excellent' => 4.0],
                'teamwork' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5],
                'recruitment' => ['min' => 4.0, 'target' => 4.5, 'excellent' => 4.8],
                'effectiveness' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5],
                'innovation' => ['min' => 3.0, 'target' => 3.5, 'excellent' => 4.0]
            ],
            'BC' => [
                'leadership' => ['min' => 2.5, 'target' => 3.0, 'excellent' => 3.5],
                'teamwork' => ['min' => 3.0, 'target' => 3.5, 'excellent' => 4.0],
                'recruitment' => ['min' => 3.5, 'target' => 4.0, 'excellent' => 4.5],
                'effectiveness' => ['min' => 3.0, 'target' => 3.5, 'excellent' => 4.0],
                'innovation' => ['min' => 2.5, 'target' => 3.0, 'excellent' => 3.5]
            ],
            'Trainee' => [
                'leadership' => ['min' => 2.0, 'target' => 2.5, 'excellent' => 3.0],
                'teamwork' => ['min' => 2.5, 'target' => 3.0, 'excellent' => 3.5],
                'recruitment' => ['min' => 2.5, 'target' => 3.0, 'excellent' => 3.5],
                'effectiveness' => ['min' => 2.5, 'target' => 3.0, 'excellent' => 3.5],
                'innovation' => ['min' => 2.0, 'target' => 2.5, 'excellent' => 3.0]
            ]
        ];

        return $benchmarks[$role] ?? $benchmarks['Trainee'];
    }

    /**
     * Update session progress with role-specific metrics
     */
    private function updateSessionProgress($session)
    {
        $responses = AuditResponse::forSession($session->session_code)->get();
        
        if ($responses->isEmpty()) {
            return;
        }

        // Calculate category scores
        $categoryScores = [];
        $categories = AuditQuestionTemplate::getCategoriesForRole($session->auditedUser->role);
        
        foreach ($categories as $category) {
            $categoryResponses = $responses->filter(function ($response) use ($category) {
                return $response->questionTemplate?->category === $category;
            });
            
            if ($categoryResponses->isNotEmpty()) {
                $categoryScores[$category] = [
                    'score' => round($categoryResponses->avg('score'), 2),
                    'percentage' => round($categoryResponses->avg('score_percentage'), 2),
                    'count' => $categoryResponses->count()
                ];
            }
        }

        // Get role benchmarks
        $benchmarks = $this->getRoleBenchmarks($session->auditedUser->role);

        // Update session
        $session->update([
            'role_audited' => $session->auditedUser->role,
            'answered_questions' => $responses->count(),
            'category_scores' => $categoryScores,
            'role_specific_metrics' => [
                'benchmarks' => $benchmarks,
                'performance_vs_benchmark' => $this->calculatePerformanceVsBenchmark($categoryScores, $benchmarks)
            ]
        ]);
    }

    /**
     * Calculate performance vs benchmarks
     */
    private function calculatePerformanceVsBenchmark($categoryScores, $benchmarks)
    {
        $performance = [];
        
        foreach ($categoryScores as $category => $data) {
            if (isset($benchmarks[$category])) {
                $score = $data['score'];
                $benchmark = $benchmarks[$category];
                
                if ($score >= $benchmark['excellent']) {
                    $level = 'excellent';
                } elseif ($score >= $benchmark['target']) {
                    $level = 'target';
                } elseif ($score >= $benchmark['min']) {
                    $level = 'min';
                } else {
                    $level = 'below_min';
                }
                
                $performance[$category] = [
                    'score' => $score,
                    'benchmark' => $benchmark,
                    'level' => $level,
                    'gap' => $benchmark['target'] - $score
                ];
            }
        }
        
        return $performance;
    }

    /**
     * Determine difficulty level based on role
     */
    private function determineDifficultyLevel($role)
    {
        $difficultyMap = [
            'CEO' => 'advanced',
            'CBO' => 'advanced',
            'Manager' => 'intermediate',
            'SBC' => 'intermediate',
            'BC' => 'intermediate',
            'Trainee' => 'basic'
        ];
        
        return $difficultyMap[$role] ?? 'intermediate';
    }

    /**
     * Seed initial question templates for all roles
     */
    public function seedInitialTemplates()
    {
        $roles = array_keys(AuditQuestionTemplate::ROLES);
        $categories = array_keys(AuditQuestionTemplate::CATEGORIES);
        
        foreach ($roles as $role) {
            $this->generateRoleSpecificQuestions($role, $categories, 3);
        }
    }
}
