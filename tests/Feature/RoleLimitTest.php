<?php

use App\Models\RoleLimit;
use Illuminate\Database\QueryException;
use Spatie\Permission\Models\Role;

it('can create a role limit for a role', function () {
    $role = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    $roleLimit = RoleLimit::create([
        'role_id' => $role->id,
        'daily_juice_limit' => 1,
        'daily_meal_limit' => 1,
        'daily_snack_limit' => 1,
        'daily_point_limit' => 300,
    ]);

    expect($roleLimit->daily_juice_limit)->toBe(1)
        ->and($roleLimit->daily_meal_limit)->toBe(1)
        ->and($roleLimit->daily_snack_limit)->toBe(1)
        ->and($roleLimit->daily_point_limit)->toBe(300);
});

it('belongs to a role', function () {
    $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

    $roleLimit = RoleLimit::create([
        'role_id' => $role->id,
        'daily_juice_limit' => 3,
        'daily_meal_limit' => 2,
        'daily_snack_limit' => 2,
        'daily_point_limit' => 500,
    ]);

    expect($roleLimit->role->name)->toBe('manager');
});

it('enforces one limit record per role', function () {
    $role = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    RoleLimit::create([
        'role_id' => $role->id,
        'daily_juice_limit' => 1,
        'daily_meal_limit' => 1,
        'daily_snack_limit' => 1,
        'daily_point_limit' => 300,
    ]);

    expect(fn () => RoleLimit::create([
        'role_id' => $role->id,
        'daily_juice_limit' => 2,
        'daily_meal_limit' => 2,
        'daily_snack_limit' => 2,
        'daily_point_limit' => 400,
    ]))->toThrow(QueryException::class);
});

it('deletes role limit when role is deleted', function () {
    $role = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    RoleLimit::create([
        'role_id' => $role->id,
        'daily_juice_limit' => 1,
        'daily_meal_limit' => 1,
        'daily_snack_limit' => 1,
        'daily_point_limit' => 300,
    ]);

    $role->delete();

    expect(RoleLimit::where('role_id', $role->id)->exists())->toBeFalse();
});
