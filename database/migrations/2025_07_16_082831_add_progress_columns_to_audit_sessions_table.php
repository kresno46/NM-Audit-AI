<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            $table->integer('total_questions')->default(0);
            $table->integer('answered_questions')->default(0);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            //
        });
    }
};
