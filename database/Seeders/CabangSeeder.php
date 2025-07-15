<?php

// database/seeders/CabangSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cabang;

class CabangSeeder extends Seeder
{
    public function run()
    {
        $cabang = [
            ['nama_cabang' => 'KPF', 'kode_cabang' => 'JKT001', 'kota' => 'Jakarta Pusat', 'alamat' => 'Jl. Sudirman No. 123', 'telepon' =>'082165487784'],
            ['nama_cabang' => 'EWF','kode_cabang' => 'JKT002', 'kota' => 'Jakarta Selatan', 'alamat' => 'Jl. Senayan No. 456', 'telepon' =>'0821998756654'],
            ['nama_cabang' => 'RFB','kode_cabang' => 'BDG001', 'kota' => 'Bandung', 'alamat' => 'Jl. Asia Afrika No. 789', 'telepon' =>'082122135541'],
            ['nama_cabang' => 'EWF','kode_cabang' => 'SBY001', 'kota' => 'Surabaya', 'alamat' => 'Jl. Tunjungan No. 321', 'telepon' =>'082122548854'],
            ['nama_cabang' => 'EWF','kode_cabang' => 'MDN001', 'kota' => 'Medan', 'alamat' => 'Jl. Gatot Subroto No. 654', 'telepon' =>'082166899898'],
        ];
        
        foreach ($cabang as $data) {
            Cabang::create($data);
        }
    }
}