<?php

use App\Models\EmployeeBalance;
use App\Models\RechargeSetting;
use App\Models\RoleLimit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    DB::table('activity_log')->truncate();
});

test('role limit changes are logged', function () {
    $role = Role::create(['name' => 'test_role']);
    $limit = RoleLimit::create([
        'role_id' => $role->id,
        'daily_juice_limit' => 100,
        'daily_meal_limit' => 200,
        'daily_snack_limit' => 50,
        'daily_point_limit' => 300,
    ]);

    $limit->update(['daily_point_limit' => 400]);

    $log = Activity::where('subject_type', RoleLimit::class)
        ->where('event', 'updated')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->subject_id)->toBe($limit->id);
    expect($log->properties['old']['daily_point_limit'])->toBe(300);
    expect($log->properties['attributes']['daily_point_limit'])->toBe(400);
});

test('recharge setting changes are logged', function () {
    $role = Role::create(['name' => 'test_role']);
    $setting = RechargeSetting::create([
        'role_id' => $role->id,
        'mode' => \App\Enums\RechargeMode::Daily,
        'recharge_time' => '09:00',
    ]);

    $setting->update([
        'mode' => \App\Enums\RechargeMode::DualPeriod,
        'breakfast_time' => '08:00',
        'lunch_time' => '12:00',
    ]);

    $log = Activity::where('subject_type', RechargeSetting::class)
        ->where('event', 'updated')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->subject_id)->toBe($setting->id);
    expect($log->properties['old']['mode'])->toBe('daily');
    expect($log->properties['attributes']['mode'])->toBe('dual_period');
    expect($log->properties['attributes']['breakfast_time'])->toBe('08:00');
    expect($log->properties['attributes']['lunch_time'])->toBe('12:00');
});

test('employee balance changes are logged', function () {
    $user = User::factory()->create();
    $balance = EmployeeBalance::create([
        'user_id' => $user->id,
        'current_balance' => 100,
        'daily_quota' => 200,
    ]);

    $balance->update(['current_balance' => 50]);

    $log = Activity::where('subject_type', EmployeeBalance::class)
        ->where('event', 'updated')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->subject_id)->toBe($balance->id);
    expect($log->properties['old']['current_balance'])->toBe(100);
    expect($log->properties['attributes']['current_balance'])->toBe(50);
});

test('empty changes are not logged when using logOnlyDirty', function () {
    $role = Role::create(['name' => 'test_role']);
    $limit = RoleLimit::create([
        'role_id' => $role->id,
        'daily_juice_limit' => 100,
        'daily_meal_limit' => 200,
        'daily_snack_limit' => 50,
        'daily_point_limit' => 300,
    ]);

    // Clear the created event log
    DB::table('activity_log')->truncate();

    // Update with same values - no actual change
    $limit->update([
        'daily_juice_limit' => 100,
        'daily_meal_limit' => 200,
        'daily_snack_limit' => 50,
        'daily_point_limit' => 300,
    ]);

    expect(Activity::where('subject_type', RoleLimit::class)->count())->toBe(0);
});

test('causer is logged when authenticated user makes changes', function () {
    $superadminRole = Role::create(['name' => 'superadmin']);
    $superadmin = User::factory()->create();
    $superadmin->assignRole($superadminRole);

    $role = Role::create(['name' => 'test_role']);

    $this->actingAs($superadmin);

    $limit = RoleLimit::create([
        'role_id' => $role->id,
        'daily_juice_limit' => 100,
        'daily_meal_limit' => 200,
        'daily_snack_limit' => 50,
        'daily_point_limit' => 300,
    ]);

    $log = Activity::where('subject_type', RoleLimit::class)
        ->where('event', 'created')
        ->first();

    expect($log->causer_id)->toBe($superadmin->id);
    expect($log->causer_type)->toBe(User::class);
});


