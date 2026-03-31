<?php

namespace Database\Seeders;

use App\Models\EmployeeBalance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Manager
        $manager = User::create([
            'name' => 'John Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'card_number' => 'MGR-0001',
            'daily_quota' => 200,
            'is_active' => true,
        ]);
        $manager->assignRole('manager');
        EmployeeBalance::create([
            'user_id' => $manager->id,
            'current_balance' => 200,
            'daily_quota' => 200,
            'last_recharged_at' => now(),
        ]);

        // Create Regular Employees
        for ($i = 1; $i <= 5; $i++) {
            $user = User::create([
                'name' => "Employee $i",
                'email' => "employee$i@example.com",
                'password' => Hash::make('password'),
                'card_number' => 'EMP-000'.$i,
                'daily_quota' => 100,
                'is_active' => true,
            ]);
            $user->assignRole('employee');
            EmployeeBalance::create([
                'user_id' => $user->id,
                'current_balance' => 100,
                'daily_quota' => 100,
                'last_recharged_at' => now(),
            ]);
        }
    }
}
