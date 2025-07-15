<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            $table->dateTime('scheduled_date')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            $table->dropColumn('scheduled_date');
        });
    }
};
