<?php

// app/Models/AuditAnswer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_session_code',
        'audit_question_id',
        'answer_text',
        'score',
        'feedback',
        'ai_evaluation',
        'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'score' => 'decimal:2',
        'ai_evaluation' => 'array',
    ];

    // Relationships
    public function auditSession(): BelongsTo
    {
        return $this->belongsTo(AuditSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AuditQuestion::class, 'audit_question_id');
    }

    // Helper methods
    public function getScorePercentage()
    {
        if (!$this->question || $this->question->max_score == 0) {
            return 0;
        }
        
        return round(($this->score / $this->question->max_score) * 100, 2);
    }

    public function getScoreColor()
    {
        $percentage = $this->getScorePercentage();
        
        if ($percentage >= 80) {
            return 'success';
        } elseif ($percentage >= 60) {
            return 'info';
        } elseif ($percentage >= 40) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}