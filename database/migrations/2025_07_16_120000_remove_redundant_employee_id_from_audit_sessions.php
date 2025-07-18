<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            // Remove redundant employee_id field since we already have audited_user_id
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            // Add back the employee_id field if we need to rollback
            $table->foreignId('employee_id')->nullable()->constrained('users');
        });
    }
};
