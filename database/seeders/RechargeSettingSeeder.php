<?php

namespace Database\Seeders;

use App\Models\RechargeSetting;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RechargeSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $managerRole = Role::where('name', 'manager')->first();
        $employeeRole = Role::where('name', 'employee')->first();

        // Default: daily recharge at midnight for both roles
        RechargeSetting::firstOrCreate(['role_id' => $managerRole->id], [
            'mode' => 'daily',
            'recharge_time' => '00:00:00',
        ]);

        RechargeSetting::firstOrCreate(['role_id' => $employeeRole->id], [
            'mode' => 'daily',
            'recharge_time' => '00:00:00',
        ]);
    }
}
