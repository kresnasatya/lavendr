<?php

namespace Database\Seeders;

use App\Models\RoleLimit;
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
        Permission::firstOrCreate(['name' => 'manage-recharge-settings']);

        // Create roles
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);

        // Assign permissions to roles
        $superadminRole->givePermissionTo(['manage-machines', 'manage-employees', 'manage-balances', 'view-reports', 'manage-recharge-settings']);
        $managerRole->givePermissionTo(['purchase', 'manage-machines', 'manage-employees', 'manage-balances', 'view-reports']);
        $employeeRole->givePermissionTo(['purchase']);

        // Seed role limits (manager and employee only; superadmin does not purchase)
        RoleLimit::firstOrCreate(['role_id' => $managerRole->id], [
            'daily_juice_limit' => 3,
            'daily_meal_limit' => 2,
            'daily_snack_limit' => 2,
            'daily_point_limit' => 500,
        ]);

        RoleLimit::firstOrCreate(['role_id' => $employeeRole->id], [
            'daily_juice_limit' => 1,
            'daily_meal_limit' => 1,
            'daily_snack_limit' => 1,
            'daily_point_limit' => 300,
        ]);
    }
}
