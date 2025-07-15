<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jabatan');
            $table->integer('level_hirarki'); // 1=CEO, 2=CBO, 3=Manager, 4=SBC, 5=BC
            $table->text('deskripsi')->nullable();
            $table->json('kompetensi_required')->nullable(); // JSON array kompetensi
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jabatan');
    }
};