<?php

// app/Models/AuditQuestion.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuditQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_session_id',
        'question_number',
        'question_text',
        'question_type',
        'category',
        'max_score',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'max_score' => 'decimal:2',
    ];

    // Relationships
    public function auditSession(): BelongsTo
    {
        return $this->belongsTo(AuditSession::class);
    }

    public function answer(): HasOne
    {
        return $this->hasOne(AuditAnswer::class);
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('question_type', $type);
    }
}