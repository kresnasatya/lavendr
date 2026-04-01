<?php

use App\Enums\RechargeMode;
use App\Models\RechargeSetting;
use Illuminate\Database\QueryException;
use Spatie\Permission\Models\Role;

it('can create a recharge setting for a role', function () {
    $role = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    $setting = RechargeSetting::create([
        'role_id' => $role->id,
        'mode' => 'daily',
        'recharge_time' => '00:00:00',
    ]);

    expect($setting->mode)->toBe(RechargeMode::Daily)
        ->and($setting->recharge_time)->toBe('00:00:00');
});

it('casts mode to RechargeMode enum', function () {
    $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

    $setting = RechargeSetting::create([
        'role_id' => $role->id,
        'mode' => 'dual_period',
        'breakfast_time' => '07:00:00',
        'lunch_time' => '12:00:00',
    ]);

    expect($setting->mode)->toBe(RechargeMode::DualPeriod)
        ->and($setting->breakfast_time)->toBe('07:00:00')
        ->and($setting->lunch_time)->toBe('12:00:00');
});

it('belongs to a role', function () {
    $role = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    $setting = RechargeSetting::create([
        'role_id' => $role->id,
        'mode' => 'daily',
        'recharge_time' => '00:00:00',
    ]);

    expect($setting->role->name)->toBe('employee');
});

it('enforces one setting per role', function () {
    $role = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    RechargeSetting::create([
        'role_id' => $role->id,
        'mode' => 'daily',
        'recharge_time' => '00:00:00',
    ]);

    expect(fn () => RechargeSetting::create([
        'role_id' => $role->id,
        'mode' => 'dual_period',
        'breakfast_time' => '07:00:00',
        'lunch_time' => '12:00:00',
    ]))->toThrow(QueryException::class);
});

it('deletes recharge setting when role is deleted', function () {
    $role = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    RechargeSetting::create([
        'role_id' => $role->id,
        'mode' => 'daily',
        'recharge_time' => '00:00:00',
    ]);

    $role->delete();

    expect(RechargeSetting::where('role_id', $role->id)->exists())->toBeFalse();
});
