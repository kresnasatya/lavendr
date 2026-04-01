<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles, permissions, and role limits
        $this->call(RolePermissionSeeder::class);

        // Seed default recharge settings per role
        $this->call(RechargeSettingSeeder::class);

        // Create machines and slots
        $this->call(MachineSeeder::class);

        // Create sample employees
        $this->call(EmployeeSeeder::class);

        // Create a test user if needed
        if (User::count() === 0) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
