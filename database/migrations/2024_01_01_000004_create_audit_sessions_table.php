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
            $table->foreignId('cabang_id')->nullable()->constrained('cabang');
            $table->foreignId('employee_id')->nullable()->constrained('users');
            $table->enum('jenis_audit', ['quarterly', 'annual', 'promotion', 'disciplinary'])->default('quarterly');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('start_time')->nullable();
            $table->integer('total_questions')->default(0);
            $table->integer('answered_questions')->default(0);
            
            // Hasil AI Analysis
            $table->json('ai_analysis')->nullable();
            $table->decimal('skor_leadership', 3, 2)->nullable();
            $table->decimal('skor_teamwork', 3, 2)->nullable();
            $table->decimal('skor_recruitment', 3, 2)->nullable();
            $table->decimal('skor_effectiveness', 3, 2)->nullable();
            $table->decimal('skor_innovation', 3, 2)->nullable();
            $table->decimal('skor_total', 3, 2)->nullable();
            
            // Rekomendasi
            $table->enum('rekomendasi_ai', ['PROMOSI', 'TETAP', 'DEMOSI'])->nullable();
            $table->text('catatan_ai')->nullable();
            $table->text('rekomendasi_ai_text')->nullable();
            $table->text('keputusan_final')->nullable();
            $table->text('catatan_auditor')->nullable();
            $table->boolean('is_overridden')->default(false);
            $table->string('recommendation')->nullable();
            $table->text('override_reason')->nullable();
            $table->foreignId('override_by')->nullable()->constrained('users');
            $table->timestamp('override_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_sessions');
    }
};