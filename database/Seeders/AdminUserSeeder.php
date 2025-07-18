<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'Administrator',
            'employee_id' => 'ADM001',
            'jabatan_id' => 1, // sesuaikan dengan jabatan administrator (kalau ada)
            'cabang_id' => 1,  // sesuaikan dengan cabang
            'atasan_id' => null,  // atau null kalau dia tertinggi
        ]);
    }
}

