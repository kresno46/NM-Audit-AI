<?php

// database/seeders/UserSeeder.php (continuation)
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Cabang;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $cabangJakartaPusat = Cabang::where('kode_cabang', 'JKT001')->first();
        $cabangJakartaSelatan = Cabang::where('kode_cabang', 'JKT002')->first();
        $cabangBandung = Cabang::where('kode_cabang', 'BDG001')->first();
        $cabangSurabaya = Cabang::where('kode_cabang', 'SBY001')->first();
        $cabangMedan = Cabang::where('kode_cabang', 'MDN001')->first();
        
        $jabatanCEO = Jabatan::where('nama_jabatan', 'CEO')->first();
        $jabatanCBO = Jabatan::where('nama_jabatan', 'CBO')->first();
        $jabatanManager = Jabatan::where('nama_jabatan', 'Manager')->first();
        $jabatanSBC = Jabatan::where('nama_jabatan', 'SBC')->first();
        $jabatanBC = Jabatan::where('nama_jabatan', 'BC')->first();
        $jabatanTrainee = Jabatan::where('nama_jabatan', 'Trainee')->first();
        
        // Create CEO
        $ceo = User::create([
            'name' => 'CEO Test',
            'email' => 'ceo@test.com',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP001',
            'role' => 'CEO',
            'cabang_id' => $cabangJakartaPusat->id,
            'jabatan_id' => $jabatanCEO->id,
            'atasan_id' => null,
            'no_hp' => '081234567890',
            'alamat' => 'Jakarta',
            'tanggal_bergabung' => now()->subYears(5),
            'status' => 'active',
        ]);
        $ceo->assignRole('CEO');
        
        // Create CBO
        $cbo = User::create([
            'name' => 'CBO Test',
            'email' => 'cbo@test.com',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP002',
            'role' => 'CBO',
            'cabang_id' => $cabangJakartaPusat->id,
            'jabatan_id' => $jabatanCBO->id,
            'atasan_id' => $ceo->id,
            'no_hp' => '081234567891',
            'alamat' => 'Jakarta',
            'tanggal_bergabung' => now()->subYears(4),
            'status' => 'active',
        ]);
        $cbo->assignRole('CBO');
        
        // Create Managers for each branch
        $managers = [
            [
                'name' => 'Manager Jakarta Pusat',
                'email' => 'manager.jktpusat@test.com',
                'employee_id' => 'EMP003',
                'cabang_id' => $cabangJakartaPusat->id,
            ],
            [
                'name' => 'Manager Jakarta Selatan',
                'email' => 'manager.jktselatan@test.com',
                'employee_id' => 'EMP004',
                'cabang_id' => $cabangJakartaSelatan->id,
            ],
            [
                'name' => 'Manager Bandung',
                'email' => 'manager.bandung@test.com',
                'employee_id' => 'EMP005',
                'cabang_id' => $cabangBandung->id,
            ],
            [
                'name' => 'Manager Surabaya',
                'email' => 'manager.surabaya@test.com',
                'employee_id' => 'EMP006',
                'cabang_id' => $cabangSurabaya->id,
            ],
            [
                'name' => 'Manager Medan',
                'email' => 'manager.medan@test.com',
                'employee_id' => 'EMP007',
                'cabang_id' => $cabangMedan->id,
            ],
        ];
        
        $managerUsers = [];
        foreach ($managers as $index => $managerData) {
            $manager = User::create([
                'name' => $managerData['name'],
                'email' => $managerData['email'],
                'password' => Hash::make('password'),
                'employee_id' => $managerData['employee_id'],
                'role' => 'Manager',
                'cabang_id' => $managerData['cabang_id'],
                'jabatan_id' => $jabatanManager->id,
                'atasan_id' => $cbo->id,
                'no_hp' => '08123456789' . ($index + 2),
                'alamat' => $managerData['name'],
                'tanggal_bergabung' => now()->subYears(3),
                'status' => 'active',
            ]);
            $manager->assignRole('Manager');
            $managerUsers[] = $manager;
        }
        
        // Create SBC employees
        $sbcEmployees = [
            ['name' => 'SBC Jakarta Pusat 1', 'email' => 'sbc.jktpusat1@test.com', 'employee_id' => 'EMP008', 'cabang_id' => $cabangJakartaPusat->id, 'manager_id' => $managerUsers[0]->id],
            ['name' => 'SBC Jakarta Pusat 2', 'email' => 'sbc.jktpusat2@test.com', 'employee_id' => 'EMP009', 'cabang_id' => $cabangJakartaPusat->id, 'manager_id' => $managerUsers[0]->id],
            ['name' => 'SBC Jakarta Selatan 1', 'email' => 'sbc.jktselatan1@test.com', 'employee_id' => 'EMP010', 'cabang_id' => $cabangJakartaSelatan->id, 'manager_id' => $managerUsers[1]->id],
            ['name' => 'SBC Jakarta Selatan 2', 'email' => 'sbc.jktselatan2@test.com', 'employee_id' => 'EMP011', 'cabang_id' => $cabangJakartaSelatan->id, 'manager_id' => $managerUsers[1]->id],
            ['name' => 'SBC Bandung 1', 'email' => 'sbc.bandung1@test.com', 'employee_id' => 'EMP012', 'cabang_id' => $cabangBandung->id, 'manager_id' => $managerUsers[2]->id],
            ['name' => 'SBC Surabaya 1', 'email' => 'sbc.surabaya1@test.com', 'employee_id' => 'EMP013', 'cabang_id' => $cabangSurabaya->id, 'manager_id' => $managerUsers[3]->id],
            ['name' => 'SBC Medan 1', 'email' => 'sbc.medan1@test.com', 'employee_id' => 'EMP014', 'cabang_id' => $cabangMedan->id, 'manager_id' => $managerUsers[4]->id],
        ];
        
        $sbcUsers = [];
        foreach ($sbcEmployees as $index => $sbcData) {
            $sbc = User::create([
                'name' => $sbcData['name'],
                'email' => $sbcData['email'],
                'password' => Hash::make('password'),
                'employee_id' => $sbcData['employee_id'],
                'role' => 'SBC',
                'cabang_id' => $sbcData['cabang_id'],
                'jabatan_id' => $jabatanSBC->id,
                'atasan_id' => $sbcData['manager_id'],
                'no_hp' => '08123456780' . ($index + 8),
                'alamat' => 'Address for ' . $sbcData['name'],
                'tanggal_bergabung' => now()->subYears(2),
                'status' => 'active',
            ]);
            $sbc->assignRole('SBC');
            $sbcUsers[] = $sbc;
        }
        
        // Create BC employees
        $bcEmployees = [
            ['name' => 'BC Jakarta Pusat 1', 'email' => 'bc.jktpusat1@test.com', 'employee_id' => 'EMP015', 'cabang_id' => $cabangJakartaPusat->id, 'sbc_id' => $sbcUsers[0]->id],
            ['name' => 'BC Jakarta Pusat 2', 'email' => 'bc.jktpusat2@test.com', 'employee_id' => 'EMP016', 'cabang_id' => $cabangJakartaPusat->id, 'sbc_id' => $sbcUsers[0]->id],
            ['name' => 'BC Jakarta Pusat 3', 'email' => 'bc.jktpusat3@test.com', 'employee_id' => 'EMP017', 'cabang_id' => $cabangJakartaPusat->id, 'sbc_id' => $sbcUsers[1]->id],
            ['name' => 'BC Jakarta Selatan 1', 'email' => 'bc.jktselatan1@test.com', 'employee_id' => 'EMP018', 'cabang_id' => $cabangJakartaSelatan->id, 'sbc_id' => $sbcUsers[2]->id],
            ['name' => 'BC Jakarta Selatan 2', 'email' => 'bc.jktselatan2@test.com', 'employee_id' => 'EMP019', 'cabang_id' => $cabangJakartaSelatan->id, 'sbc_id' => $sbcUsers[3]->id],
            ['name' => 'BC Bandung 1', 'email' => 'bc.bandung1@test.com', 'employee_id' => 'EMP020', 'cabang_id' => $cabangBandung->id, 'sbc_id' => $sbcUsers[4]->id],
            ['name' => 'BC Bandung 2', 'email' => 'bc.bandung2@test.com', 'employee_id' => 'EMP021', 'cabang_id' => $cabangBandung->id, 'sbc_id' => $sbcUsers[4]->id],
            ['name' => 'BC Surabaya 1', 'email' => 'bc.surabaya1@test.com', 'employee_id' => 'EMP022', 'cabang_id' => $cabangSurabaya->id, 'sbc_id' => $sbcUsers[5]->id],
            ['name' => 'BC Surabaya 2', 'email' => 'bc.surabaya2@test.com', 'employee_id' => 'EMP023', 'cabang_id' => $cabangSurabaya->id, 'sbc_id' => $sbcUsers[5]->id],
            ['name' => 'BC Medan 1', 'email' => 'bc.medan1@test.com', 'employee_id' => 'EMP024', 'cabang_id' => $cabangMedan->id, 'sbc_id' => $sbcUsers[6]->id],
        ];
        
        $bcUsers = [];
        foreach ($bcEmployees as $index => $bcData) {
            $bc = User::create([
                'name' => $bcData['name'],
                'email' => $bcData['email'],
                'password' => Hash::make('password'),
                'employee_id' => $bcData['employee_id'],
                'role' => 'BC',
                'cabang_id' => $bcData['cabang_id'],
                'jabatan_id' => $jabatanBC->id,
                'atasan_id' => $bcData['sbc_id'],
                'no_hp' => '08123456770' . ($index + 15),
                'alamat' => 'Address for ' . $bcData['name'],
                'tanggal_bergabung' => now()->subYears(1),
                'status' => 'active',
            ]);
            $bc->assignRole('BC');
            $bcUsers[] = $bc;
        }
        
        // Create Trainee employees
        $traineeEmployees = [
            ['name' => 'Trainee Jakarta Pusat 1', 'email' => 'trainee.jktpusat1@test.com', 'employee_id' => 'EMP025', 'cabang_id' => $cabangJakartaPusat->id, 'bc_id' => $bcUsers[0]->id],
            ['name' => 'Trainee Jakarta Pusat 2', 'email' => 'trainee.jktpusat2@test.com', 'employee_id' => 'EMP026', 'cabang_id' => $cabangJakartaPusat->id, 'bc_id' => $bcUsers[1]->id],
            ['name' => 'Trainee Jakarta Selatan 1', 'email' => 'trainee.jktselatan1@test.com', 'employee_id' => 'EMP027', 'cabang_id' => $cabangJakartaSelatan->id, 'bc_id' => $bcUsers[3]->id],
            ['name' => 'Trainee Bandung 1', 'email' => 'trainee.bandung1@test.com', 'employee_id' => 'EMP028', 'cabang_id' => $cabangBandung->id, 'bc_id' => $bcUsers[5]->id],
            ['name' => 'Trainee Surabaya 1', 'email' => 'trainee.surabaya1@test.com', 'employee_id' => 'EMP029', 'cabang_id' => $cabangSurabaya->id, 'bc_id' => $bcUsers[7]->id],
            ['name' => 'Trainee Medan 1', 'email' => 'trainee.medan1@test.com', 'employee_id' => 'EMP030', 'cabang_id' => $cabangMedan->id, 'bc_id' => $bcUsers[9]->id],
        ];
        
        foreach ($traineeEmployees as $index => $traineeData) {
            $trainee = User::create([
                'name' => $traineeData['name'],
                'email' => $traineeData['email'],
                'password' => Hash::make('password'),
                'employee_id' => $traineeData['employee_id'],
                'role' => 'Trainee',
                'cabang_id' => $traineeData['cabang_id'],
                'jabatan_id' => $jabatanTrainee->id,
                'atasan_id' => $traineeData['bc_id'],
                'no_hp' => '08123456760' . ($index + 25),
                'alamat' => 'Address for ' . $traineeData['name'],
                'tanggal_bergabung' => now()->subMonths(6),
                'status' => 'active',
            ]);
            $trainee->assignRole('Trainee');
        }
    }
}
