<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('employee_id', 20)->unique();
            $table->enum('role', ['CEO', 'CBO', 'Manager', 'SBC', 'BC', 'Trainee']);
            $table->foreignId('jabatan_id')->constrained('jabatan');
            $table->foreignId('cabang_id')->constrained('cabang');
            $table->foreignId('atasan_id')->nullable()->constrained('users');
            $table->date('tanggal_bergabung');
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->decimal('gaji_pokok', 15, 2)->nullable();
            $table->json('target_kinerja')->nullable(); // JSON untuk target per bulan/quarter
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};