<?php

use App\Enums\RechargeMode;
use App\Models\EmployeeBalance;
use App\Models\RechargeSetting;
use App\Models\RoleLimit;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles
    $employeeRole = Role::firstOrCreate(['name' => 'employee']);
    $managerRole = Role::firstOrCreate(['name' => 'manager']);

    // Set up role limits
    RoleLimit::updateOrCreate(
        ['role_id' => $employeeRole->id],
        ['daily_juice_limit' => 1, 'daily_meal_limit' => 1, 'daily_snack_limit' => 1, 'daily_point_limit' => 300]
    );

    RoleLimit::updateOrCreate(
        ['role_id' => $managerRole->id],
        ['daily_juice_limit' => 3, 'daily_meal_limit' => 2, 'daily_snack_limit' => 2, 'daily_point_limit' => 500]
    );

    // Set up recharge settings for manager (daily mode at midnight)
    RechargeSetting::updateOrCreate(
        ['role_id' => $managerRole->id],
        ['mode' => RechargeMode::Daily, 'recharge_time' => '00:00:00', 'breakfast_time' => null, 'lunch_time' => null]
    );

    // Set up recharge settings for employee (dual period mode)
    RechargeSetting::updateOrCreate(
        ['role_id' => $employeeRole->id],
        ['mode' => RechargeMode::DualPeriod, 'recharge_time' => '00:00:00', 'breakfast_time' => '07:00:00', 'lunch_time' => '12:00:00']
    );
});

test('it does not recharge when not scheduled', function () {
    $manager = makeEmployeeForRechargeTest('manager');
    $manager->balance->update(['current_balance' => 100]);

    // Current time is not 00:00, so should not recharge
    $this->artisan('balances:recharge')
        ->assertExitCode(0)
        ->expectsOutputToContain('Skipped (not scheduled for this time)');

    expect($manager->refresh()->balance->current_balance)->toBe(100);
});

test('it recharges for daily mode at exact time', function () {
    $manager = makeEmployeeForRechargeTest('manager');
    $manager->balance->update(['current_balance' => 50]);

    // Update recharge setting to current time
    $currentTime = now()->format('H:i:s');
    RechargeSetting::where('role_id', $manager->roles->first()->id)
        ->update(['recharge_time' => $currentTime]);

    $this->artisan('balances:recharge')
        ->assertExitCode(0)
        ->expectsOutputToContain('Target balance: 500 pts')
        ->expectsOutputToContain('Recharged 1 employee(s)');

    expect($manager->refresh()->balance->current_balance)->toBe(500);
});

test('it recharges for dual period mode at breakfast time', function () {
    $employee = makeEmployeeForRechargeTest('employee');
    $employee->balance->update(['current_balance' => 50]);

    // Update breakfast time to current time
    $currentTime = now()->format('H:i:s');
    RechargeSetting::where('role_id', $employee->roles->first()->id)
        ->update(['breakfast_time' => $currentTime]);

    $this->artisan('balances:recharge')
        ->assertExitCode(0)
        ->expectsOutputToContain('Target balance: 300 pts')
        ->expectsOutputToContain('Recharged 1 employee(s)');

    expect($employee->refresh()->balance->current_balance)->toBe(300);
});

test('it recharges for dual period mode at lunch time', function () {
    $employee = makeEmployeeForRechargeTest('employee');

    // Set balance to 0 to simulate full morning usage
    $employee->balance->update(['current_balance' => 0]);

    // Update lunch time to current time
    $currentTime = now()->format('H:i:s');
    RechargeSetting::where('role_id', $employee->roles->first()->id)
        ->update(['lunch_time' => $currentTime]);

    $this->artisan('balances:recharge')
        ->assertExitCode(0)
        ->expectsOutputToContain('Recharged 1 employee(s)');

    expect($employee->refresh()->balance->current_balance)->toBe(300);
});

test('it recharges only specific role when --role option is used', function () {
    $manager = makeEmployeeForRechargeTest('manager');
    $employee = makeEmployeeForRechargeTest('employee');

    $manager->balance->update(['current_balance' => 50]);
    $employee->balance->update(['current_balance' => 50]);

    // Set both to recharge now
    $currentTime = now()->format('H:i:s');
    $managerRoleId = $manager->roles->first()->id;
    RechargeSetting::where('role_id', $managerRoleId)->update(['recharge_time' => $currentTime]);
    RechargeSetting::where('role_id', $employee->roles->first()->id)->update(['breakfast_time' => $currentTime]);

    // Only recharge manager
    $this->artisan("balances:recharge --role={$managerRoleId}")
        ->assertExitCode(0)
        ->expectsOutputToContain('Processing role: manager');

    expect($manager->refresh()->balance->current_balance)->toBe(500);
    expect($employee->refresh()->balance->current_balance)->toBe(50); // Unchanged
});

test('it handles missing role limit gracefully', function () {
    // Create a role without role limit
    $testRole = Role::firstOrCreate(['name' => 'test_role']);
    RechargeSetting::updateOrCreate(
        ['role_id' => $testRole->id],
        ['mode' => RechargeMode::Daily, 'recharge_time' => now()->format('H:i:s'), 'breakfast_time' => null, 'lunch_time' => null]
    );

    $this->artisan('balances:recharge --role='.$testRole->id)
        ->assertExitCode(0)
        ->expectsOutputToContain('No role limit configured, skipping');
});

// Helper function
function makeEmployeeForRechargeTest(string $roleName): User
{
    $role = Role::where('name', $roleName)->first();

    $user = User::factory()->withCard('TEST-'.strtoupper($roleName).'-001')->create();
    $user->roles()->attach($role);

    $limit = RoleLimit::where('role_id', $role->id)->first();

    EmployeeBalance::create([
        'user_id' => $user->id,
        'current_balance' => $limit->daily_point_limit,
        'daily_quota' => $limit->daily_point_limit,
    ]);

    return $user;
}
