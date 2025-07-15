<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('audit_sessions');
            $table->integer('question_number');
            $table->string('kategori'); // leadership, teamwork, recruitment, dll
            $table->text('pertanyaan');
            $table->text('jawaban');
            $table->json('ai_sentiment')->nullable(); // JSON sentiment analysis
            $table->decimal('skor_jawaban', 3, 2)->nullable(); // skor per jawaban
            $table->text('ai_feedback')->nullable();
            $table->timestamp('answered_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};