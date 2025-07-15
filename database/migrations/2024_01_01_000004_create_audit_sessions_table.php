<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_code', 20)->unique();
            $table->foreignId('auditor_id')->constrained('users');
            $table->foreignId('audited_user_id')->constrained('users');
            $table->enum('jenis_audit', ['quarterly', 'annual', 'promotion', 'disciplinary']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            
            // Hasil AI Analysis
            $table->json('ai_analysis')->nullable(); // JSON hasil analisis GPT
            $table->decimal('skor_leadership', 3, 2)->nullable(); // 0.00 - 5.00
            $table->decimal('skor_teamwork', 3, 2)->nullable();
            $table->decimal('skor_recruitment', 3, 2)->nullable();
            $table->decimal('skor_effectiveness', 3, 2)->nullable();
            $table->decimal('skor_innovation', 3, 2)->nullable();
            $table->decimal('skor_total', 3, 2)->nullable();
            
            // Rekomendasi
            $table->enum('rekomendasi_ai', ['PROMOSI', 'TETAP', 'DEMOSI'])->nullable();
            $table->text('catatan_ai')->nullable();
            $table->enum('keputusan_final', ['PROMOSI', 'TETAP', 'DEMOSI'])->nullable();
            $table->text('catatan_auditor')->nullable();
            $table->boolean('is_overridden')->default(false); // apakah auditor override rekomendasi AI
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_sessions');
    }
};