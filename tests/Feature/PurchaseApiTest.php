<?php

use App\Enums\TransactionStatus;
use App\Models\EmployeeBalance;
use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\RoleLimit;
use App\Models\Transaction;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

function makeEmployee(string $cardNumber = 'EMP-TEST'): User
{
    $purchase = Permission::firstOrCreate(['name' => 'purchase', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    $role->givePermissionTo($purchase);

    RoleLimit::firstOrCreate(['role_id' => $role->id], [
        'daily_juice_limit' => 1,
        'daily_meal_limit' => 1,
        'daily_snack_limit' => 1,
        'daily_point_limit' => 300,
    ]);

    $user = User::factory()->withCard($cardNumber)->create();
    $user->assignRole('employee');

    EmployeeBalance::factory()->create([
        'user_id' => $user->id,
        'current_balance' => 200,
        'daily_quota' => 300,
    ]);

    return $user;
}

function makeActiveMachine(string $code = 'VM-TEST'): Machine
{
    return Machine::factory()->create(['code' => $code, 'is_active' => true]);
}

function purchasePayload(array $overrides = []): array
{
    return array_merge([
        'card_number' => 'EMP-TEST',
        'machine_id' => 'VM-TEST',
        'slot_number' => 1,
        'product_price' => 10,
    ], $overrides);
}

// ──────────────────────────────────────────────
// Validation
// ──────────────────────────────────────────────

it('rejects requests with missing fields', function () {
    $this->postJson('/api/purchase', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['card_number', 'machine_id', 'slot_number', 'product_price']);
});

// ──────────────────────────────────────────────
// Card / user checks
// ──────────────────────────────────────────────

it('rejects an unknown card number', function () {
    makeActiveMachine();

    $this->postJson('/api/purchase', purchasePayload(['card_number' => 'UNKNOWN']))
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Card not recognised or employee is inactive.']);
});

it('rejects an inactive employee', function () {
    makeActiveMachine();
    $role = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    $user = User::factory()->withCard('EMP-TEST')->inactive()->create();
    $user->assignRole('employee');

    $this->postJson('/api/purchase', purchasePayload())
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Card not recognised or employee is inactive.']);
});

it('rejects a user without purchase permission', function () {
    makeActiveMachine();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    $user = User::factory()->withCard('EMP-TEST')->create();
    $user->assignRole('superadmin');
    EmployeeBalance::factory()->create(['user_id' => $user->id, 'current_balance' => 200]);

    $this->postJson('/api/purchase', purchasePayload())
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Employee is not authorised to purchase.']);
});

// ──────────────────────────────────────────────
// Machine / slot checks
// ──────────────────────────────────────────────

it('rejects an unknown machine', function () {
    makeEmployee();

    $this->postJson('/api/purchase', purchasePayload(['machine_id' => 'UNKNOWN-VM']))
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Vending machine not found or inactive.']);
});

it('rejects an inactive machine', function () {
    makeEmployee();
    Machine::factory()->create(['code' => 'VM-TEST', 'is_active' => false]);

    $this->postJson('/api/purchase', purchasePayload())
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Vending machine not found or inactive.']);
});

it('rejects an unknown slot number', function () {
    makeEmployee();
    makeActiveMachine();

    $this->postJson('/api/purchase', purchasePayload(['slot_number' => 99]))
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Slot not found on this machine.']);
});

it('rejects a product price mismatch', function () {
    makeEmployee();
    $machine = makeActiveMachine();
    MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    $this->postJson('/api/purchase', purchasePayload(['product_price' => 99]))
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Product price mismatch.']);
});

// ──────────────────────────────────────────────
// Balance check
// ──────────────────────────────────────────────

it('rejects purchase when balance is insufficient', function () {
    $purchase = Permission::firstOrCreate(['name' => 'purchase', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    $role->givePermissionTo($purchase);
    RoleLimit::firstOrCreate(['role_id' => $role->id], [
        'daily_juice_limit' => 1, 'daily_meal_limit' => 1, 'daily_snack_limit' => 1, 'daily_point_limit' => 300,
    ]);
    $user = User::factory()->withCard('EMP-TEST')->create();
    $user->assignRole('employee');
    EmployeeBalance::factory()->create(['user_id' => $user->id, 'current_balance' => 5]);

    $machine = makeActiveMachine();
    MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    $this->postJson('/api/purchase', purchasePayload(['product_price' => 10]))
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Insufficient balance.']);

    expect(Transaction::where('status', TransactionStatus::Failed)->count())->toBe(1);
});

// ──────────────────────────────────────────────
// Category limit check
// ──────────────────────────────────────────────

it('rejects purchase when daily category limit is reached', function () {
    $user = makeEmployee();
    $machine = makeActiveMachine();
    $slot = MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    // Simulate today's successful juice purchase already consumed
    Transaction::factory()->create([
        'user_id' => $user->id,
        'machine_id' => $machine->id,
        'machine_slot_id' => $slot->id,
        'status' => TransactionStatus::Success,
    ]);

    $this->postJson('/api/purchase', purchasePayload(['product_price' => 10]))
        ->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Daily juice limit reached.']);
});

// ──────────────────────────────────────────────
// Happy path
// ──────────────────────────────────────────────

it('processes a successful purchase and deducts balance', function () {
    $user = makeEmployee();
    $machine = makeActiveMachine();
    MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    $this->postJson('/api/purchase', purchasePayload(['product_price' => 10]))
        ->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Purchase successful.'])
        ->assertJsonPath('data.points_deducted', 10)
        ->assertJsonPath('data.remaining_balance', 190);

    expect(Transaction::where('status', TransactionStatus::Success)->count())->toBe(1);
    expect($user->balance->fresh()->current_balance)->toBe(190);
});

it('allows different category purchases on the same day', function () {
    $user = makeEmployee();
    $machine = makeActiveMachine();
    $juiceSlot = MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);
    MachineSlot::factory()->meal()->create(['machine_id' => $machine->id, 'slot_number' => 11, 'price' => 20]);

    // Use up juice quota
    Transaction::factory()->create([
        'user_id' => $user->id,
        'machine_id' => $machine->id,
        'machine_slot_id' => $juiceSlot->id,
        'status' => TransactionStatus::Success,
    ]);

    // Meal purchase should still succeed
    $this->postJson('/api/purchase', purchasePayload(['slot_number' => 11, 'product_price' => 20]))
        ->assertStatus(200)
        ->assertJson(['success' => true]);
});
