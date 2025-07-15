<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatan';
    
    protected $fillable = [
        'nama_jabatan', 'level_hirarki', 'deskripsi', 'kompetensi_required'
    ];

    protected $casts = [
        'kompetensi_required' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Get audit questions berdasarkan jabatan
    public function getAuditQuestions()
    {
        $questions = [
            'leadership' => [
                'Bagaimana cara Anda memimpin tim untuk mencapai target penjualan?',
                'Ceritakan tentang situasi sulit yang pernah Anda hadapi sebagai leader dan bagaimana mengatasinya?',
                'Bagaimana Anda memotivasi tim yang sedang down karena tidak mencapai target?'
            ],
            'teamwork' => [
                'Bagaimana Anda berkolaborasi dengan tim untuk mencapai tujuan bersama?',
                'Ceritakan pengalaman Anda mengatasi konflik dalam tim.',
                'Bagaimana cara Anda berbagi pengetahuan dengan rekan kerja?'
            ],
            'recruitment' => [
                'Bagaimana strategi Anda dalam merekrut nasabah baru?',
                'Apa yang menjadi key success factor dalam closing deal?',
                'Bagaimana Anda mengatasi nasabah yang ragu-ragu untuk investasi?'
            ],
            'effectiveness' => [
                'Bagaimana Anda mengelola waktu untuk mencapai target optimal?',
                'Ceritakan tentang strategi Anda dalam meningkatkan produktivitas.',
                'Bagaimana cara Anda mengukur keberhasilan pekerjaan Anda?'
            ],
            'innovation' => [
                'Ide inovatif apa yang pernah Anda implementasikan?',
                'Bagaimana Anda beradaptasi dengan perubahan market?',
                'Apa rencana Anda untuk mengembangkan bisnis ke depan?'
            ]
        ];

        return $questions;
    }
}