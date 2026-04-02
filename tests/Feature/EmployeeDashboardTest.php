<?php

use App\Enums\TransactionStatus;
use App\Models\EmployeeBalance;
use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\RoleLimit;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

// ──────────────────────────────────────────────
// Helpers (reuse pattern from PurchaseApiTest)
// ──────────────────────────────────────────────

function makeEmployeeUser(string $cardNumber = 'CARD-001', int $balance = 200): User
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
    EmployeeBalance::factory()->create(['user_id' => $user->id, 'current_balance' => $balance]);

    return $user;
}

// ──────────────────────────────────────────────
// Page access
// ──────────────────────────────────────────────

it('redirects guests away from the dashboard', function () {
    get('/dashboard')->assertRedirect('/login');
});

it('renders the dashboard for authenticated employees', function () {
    $user = makeEmployeeUser();

    Livewire::actingAs($user)->test('pages::employee-dashboard')
        ->assertSee('My Dashboard');
});

// ──────────────────────────────────────────────
// Balance display
// ──────────────────────────────────────────────

it('shows the employee current balance', function () {
    $user = makeEmployeeUser(balance: 150);

    Livewire::actingAs($user)->test('pages::employee-dashboard')
        ->assertSeeHtml('150 pts');
});

// ──────────────────────────────────────────────
// Machine selection
// ──────────────────────────────────────────────

it('shows active machines', function () {
    $user = makeEmployeeUser();
    Machine::factory()->create(['code' => 'VM-001', 'is_active' => true]);

    Livewire::actingAs($user)->test('pages::employee-dashboard')
        ->assertSee('VM-001');
});

it('selects a machine and shows its slots', function () {
    $user = makeEmployeeUser();
    $machine = Machine::factory()->create(['code' => 'VM-001', 'is_active' => true]);
    MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    Livewire::actingAs($user)->test('pages::employee-dashboard')
        ->call('selectMachine', $machine->id)
        ->assertSet('selectedMachineId', $machine->id)
        ->assertSee('Slot 1');
});

// ──────────────────────────────────────────────
// Slot selection → confirm modal
// ──────────────────────────────────────────────

it('opens confirm modal when a slot is selected', function () {
    $user = makeEmployeeUser();
    $machine = Machine::factory()->create(['is_active' => true]);
    $slot = MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    Livewire::actingAs($user)->test('pages::employee-dashboard')
        ->call('selectMachine', $machine->id)
        ->call('selectSlot', $slot->id)
        ->assertSet('showConfirmModal', true)
        ->assertSet('selectedSlotId', $slot->id);
});

// ──────────────────────────────────────────────
// Purchase
// ──────────────────────────────────────────────

it('completes a purchase and shows success message', function () {
    $user = makeEmployeeUser(balance: 200);
    $machine = Machine::factory()->create(['code' => 'VM-TEST', 'is_active' => true]);
    MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    Livewire::actingAs($user)->test('pages::employee-dashboard')
        ->call('selectMachine', $machine->id)
        ->call('selectSlot', MachineSlot::first()->id)
        ->call('purchase')
        ->assertSet('resultSuccess', true)
        ->assertSet('showConfirmModal', false);

    expect($user->balance->fresh()->current_balance)->toBe(190);
    expect(Transaction::where('status', TransactionStatus::Success)->count())->toBe(1);
});

it('shows error when employee has no card number', function () {
    $purchase = Permission::firstOrCreate(['name' => 'purchase', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    $role->givePermissionTo($purchase);
    $user = User::factory()->create(['card_number' => null]);
    $user->assignRole('employee');
    EmployeeBalance::factory()->create(['user_id' => $user->id, 'current_balance' => 200]);

    $machine = Machine::factory()->create(['is_active' => true]);
    $slot = MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    Livewire::actingAs($user)->test('pages::employee-dashboard')
        ->call('selectMachine', $machine->id)
        ->call('selectSlot', $slot->id)
        ->call('purchase')
        ->assertSet('resultSuccess', false)
        ->assertSet('resultMessage', 'Your account has no card number assigned. Contact an administrator.');
});

it('shows failure message when purchase is rejected', function () {
    $user = makeEmployeeUser(balance: 5); // not enough balance
    $machine = Machine::factory()->create(['code' => 'VM-TEST', 'is_active' => true]);
    MachineSlot::factory()->juice()->create(['machine_id' => $machine->id, 'slot_number' => 1, 'price' => 10]);

    Livewire::actingAs($user)->test('pages::employee-dashboard')
        ->call('selectMachine', $machine->id)
        ->call('selectSlot', MachineSlot::first()->id)
        ->call('purchase')
        ->assertSet('resultSuccess', false)
        ->assertSee('Insufficient balance');
});
