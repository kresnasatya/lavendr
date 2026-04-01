<?php

use Livewire\Component;
use Livewire\Attributes\{Validate, Computed};
use App\Models\User;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public $showModal = false;
    public $editingEmployee = null;
    public $name = '';
    public $email = '';
    public $cardNumber = '';
    public $dailyQuota = 100;
    public $selectedRole = '';
    public $searchTerm = '';
    public $isActive = true;

    #[Computed]
    public function employees()
    {
        return User::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('email', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('card_number', 'like', '%' . $this->searchTerm . '%')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    public function openModal($employeeId = null)
    {
        if ($employeeId) {
            $employee = User::find($employeeId);
            $this->editingEmployee = $employeeId;
            $this->name = $employee->name;
            $this->email = $employee->email;
            $this->cardNumber = $employee->card_number;
            $this->dailyQuota = $employee->daily_quota;
            $this->isActive = $employee->is_active;
            $this->selectedRole = $employee->roles->first()?->id ?? '';
        } else {
            $this->resetForm();
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingEmployee = null;
        $this->name = '';
        $this->email = '';
        $this->cardNumber = '';
        $this->dailyQuota = 100;
        $this->isActive = true;
        $this->selectedRole = '';
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email,' . ($this->editingEmployee ?? 'NULL'),
            'cardNumber' => 'required|string|unique:users,card_number,' . ($this->editingEmployee ?? 'NULL'),
            'dailyQuota' => 'required|integer|min:0',
            'selectedRole' => 'required',
        ]);

        if ($this->editingEmployee) {
            $employee = User::find($this->editingEmployee);
            $employee->update([
                'name' => $this->name,
                'email' => $this->email,
                'card_number' => $this->cardNumber,
                'daily_quota' => $this->dailyQuota,
                'is_active' => $this->isActive,
            ]);
        } else {
            $employee = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'card_number' => $this->cardNumber,
                'daily_quota' => $this->dailyQuota,
                'password' => bcrypt(uniqid()),
                'is_active' => $this->isActive,
            ]);
        }

        $employee->syncRoles([Role::find($this->selectedRole)]);
        $this->closeModal();
    }

    public function deleteEmployee($employeeId)
    {
        User::find($employeeId)->delete();
    }

    public function toggleActive($employeeId)
    {
        $employee = User::find($employeeId);
        $employee->update(['is_active' => !$employee->is_active]);
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <div>
            <flux:heading size="xl">Manage Employees</flux:heading>
            <p class="text-zinc-600 dark:text-zinc-400">Add and manage employee accounts</p>
        </div>
        <flux:button wire:click="openModal" variant="primary">
            <flux:icon icon="plus" />
            Add Employee
        </flux:button>
    </div>

    <!-- Search -->
    <flux:field>
        <flux:label>Search Employees</flux:label>
        <flux:input wire:model.live="searchTerm" type="text" placeholder="Search by name, email, or card number..." />
    </flux:field>

    <!-- Employees Table -->
    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Card Number</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Role</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Daily Quota</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Status</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->employees as $employee)
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm">
                            <div>
                                <p class="font-medium">{{ $employee->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $employee->email }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-mono">{{ $employee->card_number }}</td>
                        <td class="px-6 py-4 text-sm">
                            <flux:badge>{{ $employee->roles->first()?->name ?? 'No Role' }}</flux:badge>
                        </td>
                        <td class="px-6 py-4 text-sm">${{ number_format($employee->daily_quota / 100, 2) }}</td>
                        <td class="px-6 py-4 text-center text-sm">
                            @if($employee->is_active)
                                <flux:badge variant="success">Active</flux:badge>
                            @else
                                <flux:badge variant="danger">Inactive</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <flux:button wire:click="openModal({{ $employee->id }})" size="sm" variant="subtle">
                                Edit
                            </flux:button>
                            <flux:button wire:click="toggleActive({{ $employee->id }})" size="sm" variant="ghost">
                                {{ $employee->is_active ? 'Disable' : 'Enable' }}
                            </flux:button>
                            <flux:button wire:click="deleteEmployee({{ $employee->id }})" size="sm" variant="danger">
                                Delete
                            </flux:button>
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

    <!-- Modal -->
    <flux:modal wire:model="showModal">
        <flux:heading>{{ $editingEmployee ? 'Edit Employee' : 'Add Employee' }}</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input type="text" wire:model="name" placeholder="Full Name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" placeholder="email@example.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Card Number</flux:label>
                <flux:input type="text" wire:model="cardNumber" placeholder="e.g., E001" />
                <flux:error name="cardNumber" />
            </flux:field>

            <flux:field>
                <flux:label>Daily Quota (in cents)</flux:label>
                <flux:input type="number" wire:model="dailyQuota" placeholder="100" />
                <flux:error name="dailyQuota" />
            </flux:field>

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="selectedRole">
                    <option value="">Select a role...</option>
                    @foreach($this->roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedRole" />
            </flux:field>

            <label class="flex items-center space-x-2">
                <flux:checkbox wire:model="isActive" />
                <span class="text-sm">Active</span>
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">Save Employee</flux:button>
        </div>
    </flux:modal>
</div>
