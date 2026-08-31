<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Doctor', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Patient', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Nurse', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Receptionist', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Pharmacist', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Accountant', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Case Manager', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Lab Technician', 'is_default' => 0, 'guard_name' => 'web'],
            ['name' => 'Super Admin', 'is_default' => 0, 'guard_name' => 'web'],
        ];

        // Insert the roles into the database
        foreach ($roles as $role) {
            Role::create($role);
        }

    }
}
