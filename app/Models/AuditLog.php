<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id', 'question_number', 'kategori', 'pertanyaan', 
        'jawaban', 'ai_sentiment', 'skor_jawaban', 'ai_feedback', 'answered_at'
    ];

    protected $casts = [
        'ai_sentiment' => 'array',
        'skor_jawaban' => 'decimal:2',
        'answered_at' => 'datetime',
    ];

    public function auditSession()
    {
        return $this->belongsTo(AuditSession::class, 'session_id');
    }

    // Get sentiment color class
    public function getSentimentColorClass()
    {
        $sentiment = $this->ai_sentiment['overall'] ?? 'neutral';
        
        switch ($sentiment) {
            case 'positive':
                return 'text-green-600';
            case 'negative':
                return 'text-red-600';
            default:
                return 'text-gray-600';
        }
    }
}