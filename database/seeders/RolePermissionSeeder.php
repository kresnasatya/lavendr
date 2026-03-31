<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        Permission::firstOrCreate(['name' => 'purchase']);
        Permission::firstOrCreate(['name' => 'manage-machines']);
        Permission::firstOrCreate(['name' => 'manage-employees']);
        Permission::firstOrCreate(['name' => 'manage-balances']);
        Permission::firstOrCreate(['name' => 'view-reports']);

        // Create roles
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);

        // Assign permissions to roles
        $managerRole->givePermissionTo(['purchase', 'manage-machines', 'manage-employees', 'manage-balances', 'view-reports']);
        $employeeRole->givePermissionTo(['purchase']);
    }
}
