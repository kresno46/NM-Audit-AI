<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        $permissions = [
            'view_dashboard',
            'create_audit',
            'view_audit', 
            'edit_audit',
            'delete_audit',
            'create_employee',
            'view_employee',
            'edit_employee',
            'delete_employee',
            'view_reports',
            'export_reports',
            'manage_users',
            'manage_settings'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $auditorRole = Role::firstOrCreate(['name' => 'auditor']);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer']);

        // Create additional roles used in UserSeeder
        $ceoRole = Role::firstOrCreate(['name' => 'CEO']);
        $cboRole = Role::firstOrCreate(['name' => 'CBO']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $sbcRole = Role::firstOrCreate(['name' => 'SBC']);
        $bcRole = Role::firstOrCreate(['name' => 'BC']);
        $traineeRole = Role::firstOrCreate(['name' => 'Trainee']);

        // Assign permissions to roles
        $adminRole->givePermissionTo($permissions);
        
        $auditorRole->givePermissionTo([
            'view_dashboard',
            'create_audit',
            'view_audit',
            'edit_audit',
            'view_employee',
            'view_reports'
        ]);

        $viewerRole->givePermissionTo([
            'view_dashboard',
            'view_audit',
            'view_employee',
            'view_reports'
        ]);
    }
}