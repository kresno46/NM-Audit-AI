<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_question_templates', function (Blueprint $table) {
            $table->id();
            $table->string('role', 50)->index(); // CEO, CBO, Manager, SBC, BC, Trainee
            $table->string('category', 50)->index(); // leadership, teamwork, recruitment, effectiveness, innovation
            $table->text('question_text');
            $table->enum('question_type', ['open-ended', 'multiple-choice', 'rating', 'yes-no'])->default('open-ended');
            $table->decimal('max_score', 3, 2)->default(5.00);
            $table->enum('difficulty_level', ['basic', 'intermediate', 'advanced'])->default('intermediate');
            $table->json('options')->nullable(); // For multiple-choice questions
            $table->text('guidelines')->nullable(); // Guidelines for answering
            $table->text('expected_response')->nullable(); // Expected response pattern
            $table->boolean('is_active')->default(true);
            $table->integer('order_index')->default(0);
            $table->timestamps();
            
            $table->index(['role', 'category', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_question_templates');
    }
};
