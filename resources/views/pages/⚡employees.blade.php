<?php

use App\Models\EmployeeBalance;
use App\Models\RoleLimit;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public bool $showModal = false;
    public ?int $editingEmployee = null;
    public string $name = '';
    public string $email = '';
    public string $cardNumber = '';
    public ?int $selectedRole = null;
    public bool $isActive = true;

    #[Computed]
    public function employees(): \Illuminate\Database\Eloquent\Collection
    {
        return User::with(['roles', 'balance'])
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('card_number', 'like', '%'.$this->searchTerm.'%');
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    public string $searchTerm = '';

    public function openModal(?int $employeeId = null): void
    {
        if ($employeeId) {
            $employee = User::with('roles')->findOrFail($employeeId);
            $this->editingEmployee = $employeeId;
            $this->name = $employee->name;
            $this->email = $employee->email;
            $this->cardNumber = $employee->card_number ?? '';
            $this->isActive = $employee->is_active;
            $this->selectedRole = $employee->roles->first()?->id;
        } else {
            $this->resetForm();
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'name'         => 'required|string|min:2|max:255',
            'email'        => 'required|email|unique:users,email,'.($this->editingEmployee ?? 'NULL'),
            'cardNumber'   => 'nullable|string|unique:users,card_number,'.($this->editingEmployee ?? 'NULL'),
            'selectedRole' => 'required|exists:roles,id',
        ]);

        if ($this->editingEmployee) {
            $employee = User::findOrFail($this->editingEmployee);
            $employee->update([
                'name'        => $this->name,
                'email'       => $this->email,
                'card_number' => $this->cardNumber ?: null,
                'is_active'   => $this->isActive,
            ]);
        } else {
            $employee = User::create([
                'name'        => $this->name,
                'email'       => $this->email,
                'card_number' => $this->cardNumber ?: null,
                'is_active'   => $this->isActive,
                'password'    => bcrypt(str()->random(16)),
            ]);

            $role = Role::find($this->selectedRole);
            $dailyQuota = RoleLimit::where('role_id', $role?->id)->value('daily_point_limit') ?? 0;

            EmployeeBalance::create([
                'user_id'         => $employee->id,
                'current_balance' => $dailyQuota,
                'daily_quota'     => $dailyQuota,
            ]);
        }

        $employee->syncRoles([$this->selectedRole]);

        unset($this->employees);
        $this->closeModal();
    }

    public function deleteEmployee(int $employeeId): void
    {
        User::findOrFail($employeeId)->delete();
        unset($this->employees);
    }

    public function toggleActive(int $employeeId): void
    {
        $employee = User::findOrFail($employeeId);
        $employee->update(['is_active' => ! $employee->is_active]);
        unset($this->employees);
    }

    private function resetForm(): void
    {
        $this->editingEmployee = null;
        $this->name = '';
        $this->email = '';
        $this->cardNumber = '';
        $this->isActive = true;
        $this->selectedRole = null;
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <div>
            <flux:heading size="xl">Manage Employees</flux:heading>
            <flux:text>Add and manage employee accounts</flux:text>
        </div>
        <flux:button wire:click="openModal" variant="primary" icon="plus">
            Add Employee
        </flux:button>
    </div>

    <flux:field>
        <flux:label>Search</flux:label>
        <flux:input wire:model.live="searchTerm" type="text" placeholder="Name, email, or card number…" />
    </flux:field>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Card Number</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Role</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Balance</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Status</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->employees as $employee)
                    <tr wire:key="emp-{{ $employee->id }}" class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $employee->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $employee->email }}</p>
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $employee->card_number ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <flux:badge>{{ $employee->roles->first()?->name ?? 'No Role' }}</flux:badge>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ $employee->balance?->current_balance ?? 0 }} pts
                        </td>
                        <td class="px-6 py-4 text-center text-sm">
                            @if ($employee->is_active)
                                <flux:badge variant="success">Active</flux:badge>
                            @else
                                <flux:badge variant="danger">Inactive</flux:badge>
                            @endif
                        </td>
                        <td class="space-x-1 px-6 py-4 text-right text-sm">
                            <flux:button wire:click="openModal({{ $employee->id }})" size="sm" variant="subtle">Edit</flux:button>
                            <flux:button wire:click="toggleActive({{ $employee->id }})" size="sm" variant="ghost">
                                {{ $employee->is_active ? 'Disable' : 'Enable' }}
                            </flux:button>
                            <flux:button wire:click="deleteEmployee({{ $employee->id }})" size="sm" variant="danger">Delete</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-500">No employees found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal wire:model="showModal" class="max-w-md">
        <flux:heading>{{ $editingEmployee ? 'Edit Employee' : 'Add Employee' }}</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input type="text" wire:model="name" placeholder="Full name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" placeholder="email@example.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Card Number</flux:label>
                <flux:input type="text" wire:model="cardNumber" placeholder="e.g., CARD-001 (optional)" />
                <flux:error name="cardNumber" />
            </flux:field>

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="selectedRole">
                    <flux:select.option value="">Select a role…</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ ucfirst($role->name) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedRole" />
            </flux:field>

            <div class="flex items-center gap-2">
                <flux:checkbox wire:model="isActive" id="isActive" />
                <flux:label for="isActive">Active</flux:label>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">Save</flux:button>
        </div>
    </flux:modal>
</div>
