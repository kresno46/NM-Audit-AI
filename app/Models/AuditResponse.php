<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_session_code',
        'audit_question_template_id',
        'audit_question_id',
        'answer_text',
        'score',
        'score_percentage',
        'feedback',
        'chatgpt_feedback',
        'ai_evaluation',
        'sentiment_analysis',
        'key_insights',
        'improvement_suggestions',
        'answered_at',
        'time_taken_seconds',
        'is_flagged',
        'flag_reason'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'score_percentage' => 'decimal:2',
        'ai_evaluation' => 'array',
        'sentiment_analysis' => 'array',
        'key_insights' => 'array',
        'improvement_suggestions' => 'array',
        'answered_at' => 'datetime',
        'time_taken_seconds' => 'integer',
        'is_flagged' => 'boolean'
    ];

    /**
     * Relationship with audit session
     */
    public function auditSession()
    {
        return $this->belongsTo(AuditSession::class, 'audit_session_code', 'session_code');
    }

    /**
     * Relationship with question template
     */
    public function questionTemplate()
    {
        return $this->belongsTo(AuditQuestionTemplate::class, 'audit_question_template_id');
    }

    /**
     * Relationship with audit question (legacy)
     */
    public function auditQuestion()
    {
        return $this->belongsTo(AuditQuestion::class, 'audit_question_id');
    }

    /**
     * Scope for responses in a session
     */
    public function scopeForSession($query, $sessionCode)
    {
        return $query->where('audit_session_code', $sessionCode);
    }

    /**
     * Scope for flagged responses
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Get average score for a session
     */
    public static function getAverageScoreForSession($sessionCode)
    {
        return self::forSession($sessionCode)->avg('score') ?? 0;
    }

    /**
     * Get responses grouped by category
     */
    public static function getResponsesByCategory($sessionCode)
    {
        return self::forSession($sessionCode)
            ->with(['questionTemplate'])
            ->get()
            ->groupBy(function ($response) {
                return $response->questionTemplate?->category ?? 'uncategorized';
            });
    }

    /**
     * Get performance summary
     */
    public static function getPerformanceSummary($sessionCode)
    {
        $responses = self::forSession($sessionCode)->get();
        
        if ($responses->isEmpty()) {
            return [
                'total_responses' => 0,
                'average_score' => 0,
                'average_percentage' => 0,
                'categories' => [],
                'strengths' => [],
                'improvements' => []
            ];
        }

        $categories = $responses->groupBy(function ($response) {
            return $response->questionTemplate?->category ?? 'uncategorized';
        });

        $categoryScores = [];
        foreach ($categories as $category => $categoryResponses) {
            $categoryScores[$category] = [
                'count' => $categoryResponses->count(),
                'average_score' => round($categoryResponses->avg('score'), 2),
                'average_percentage' => round($categoryResponses->avg('score_percentage'), 2),
                'responses' => $categoryResponses
            ];
        }

        // Extract strengths and improvements from AI feedback
        $strengths = [];
        $improvements = [];
        
        foreach ($responses as $response) {
            if ($response->key_insights && is_array($response->key_insights)) {
                $strengths = array_merge($strengths, $response->key_insights);
            }
            
            if ($response->improvement_suggestions && is_array($response->improvement_suggestions)) {
                $improvements = array_merge($improvements, $response->improvement_suggestions);
            }
        }

        return [
            'total_responses' => $responses->count(),
            'average_score' => round($responses->avg('score'), 2),
            'average_percentage' => round($responses->avg('score_percentage'), 2),
            'categories' => $categoryScores,
            'strengths' => array_unique($strengths),
            'improvements' => array_unique($improvements)
        ];
    }
}
