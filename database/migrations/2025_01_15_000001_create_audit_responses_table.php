<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_responses', function (Blueprint $table) {
            $table->id();
            $table->string('audit_session_code', 20)->index();
            $table->foreignId('audit_question_template_id')->nullable()->constrained('audit_question_templates');
            $table->foreignId('audit_question_id')->nullable()->constrained('audit_questions');
            $table->text('answer_text');
            $table->decimal('score', 3, 2)->default(0.00);
            $table->decimal('score_percentage', 5, 2)->default(0.00);
            $table->text('feedback')->nullable();
            $table->text('chatgpt_feedback')->nullable();
            $table->json('ai_evaluation')->nullable();
            $table->json('sentiment_analysis')->nullable();
            $table->json('key_insights')->nullable();
            $table->json('improvement_suggestions')->nullable();
            $table->datetime('answered_at');
            $table->integer('time_taken_seconds')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('audit_session_code')->references('session_code')->on('audit_sessions')->onDelete('cascade');
            $table->index(['audit_session_code', 'answered_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_responses');
    }
};
