<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            $table->string('role_audited')->nullable()->after('audited_user_id');
            $table->json('question_template_ids')->nullable()->after('total_questions');
            $table->json('category_scores')->nullable()->after('skor_innovation');
            $table->json('role_specific_metrics')->nullable()->after('ai_analysis');
            $table->text('chatgpt_summary')->nullable()->after('catatan_ai');
            $table->json('role_benchmarks')->nullable()->after('rekomendasi_ai_text');
        });
    }

    public function down()
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'role_audited',
                'question_template_ids',
                'category_scores',
                'role_specific_metrics',
                'chatgpt_summary',
                'role_benchmarks'
            ]);
        });
    }
};
