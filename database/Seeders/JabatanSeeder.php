<?php

// database/seeders/JabatanSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jabatan;

class JabatanSeeder extends Seeder
{
    public function run()
    {
        $jabatan = [
            ['nama_jabatan' => 'CEO', 'level_hirarki' => 1, 'deskripsi' => 'Chief Executive Officer'],
            ['nama_jabatan' => 'CBO', 'level_hirarki' => 2, 'deskripsi' => 'Chief Business Officer'],
            ['nama_jabatan' => 'Manager', 'level_hirarki' => 3, 'deskripsi' => 'Branch Manager'],
            ['nama_jabatan' => 'SBC', 'level_hirarki' => 4, 'deskripsi' => 'Senior Business Consultant'],
            ['nama_jabatan' => 'BC', 'level_hirarki' => 5, 'deskripsi' => 'Business Consultant'],
            ['nama_jabatan' => 'Trainee', 'level_hirarki' => 6, 'deskripsi' => 'Management Trainee'],
        ];
        
        foreach ($jabatan as $data) {
            Jabatan::create($data);
        }
    }
}