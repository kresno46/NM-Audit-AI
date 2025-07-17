<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_code', 'auditor_id', 'audited_user_id', 'jenis_audit', 
        'status', 'started_at', 'completed_at', 'ai_analysis',
        'skor_leadership', 'skor_teamwork', 'skor_recruitment', 
        'skor_effectiveness', 'skor_innovation', 'skor_total',
        'rekomendasi_ai', 'catatan_ai', 'keputusan_final', 
        'catatan_auditor', 'is_overridden'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'ai_analysis' => 'array',
        'skor_leadership' => 'decimal:2',
        'skor_teamwork' => 'decimal:2',
        'skor_recruitment' => 'decimal:2',
        'skor_effectiveness' => 'decimal:2',
        'skor_innovation' => 'decimal:2',
        'skor_total' => 'decimal:2',
        'is_overridden' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->session_code = 'AUD-' . strtoupper(Str::random(10));
        });
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }


    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function auditedUser()
    {
        return $this->belongsTo(User::class, 'audited_user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'audited_user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'session_code');
    }

    public function questions()
    {
        return $this->hasMany(AuditQuestion::class, 'audit_session_code', 'id');
    }

    public function answers()
    {
        return $this->hasMany(AuditAnswer::class, 'audit_session_code', 'id');
    }

    public function getProgressPercentage()
    {
        if ($this->total_questions == 0) return 0;
        return round(($this->answered_questions / $this->total_questions) * 100, 2);
    }

    public function getDurationMinutes()
    {
        if (!$this->start_time) return 0;
        return now()->diffInMinutes($this->start_time);
    }



    // Calculate total score
    public function calculateTotalScore()
    {
        $total = ($this->skor_leadership + $this->skor_teamwork + 
                 $this->skor_recruitment + $this->skor_effectiveness + 
                 $this->skor_innovation) / 5;
        
        $this->skor_total = round($total, 2);
        $this->save();
        
        return $this->skor_total;
    }

    // Get recommendation based on score
    public function getRecommendation()
    {
        $score = $this->skor_total;
        
        if ($score >= 4.0) {
            return 'PROMOSI';
        } elseif ($score >= 3.0) {
            return 'TETAP';
        } else {
            return 'DEMOSI';
        }
    }

    // Get score color class for UI
    public function getScoreColorClass()
    {
        $score = $this->skor_total;
        
        if ($score >= 4.0) {
            return 'text-green-600';
        } elseif ($score >= 3.0) {
            return 'text-yellow-600';
        } else {
            return 'text-red-600';
        }
    }

    // Get recommendation badge class
    public function getRecommendationBadgeClass()
    {
        switch ($this->rekomendasi_ai) {
            case 'PROMOSI':
                return 'bg-green-100 text-green-800';
            case 'TETAP':
                return 'bg-yellow-100 text-yellow-800';
            case 'DEMOSI':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
}
