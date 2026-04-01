<?php

use Livewire\Component;
use Livewire\Attributes\{Validate, Computed};
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component
{
    public $showModal = false;
    public $editingRole = null;
    public $roleName = '';
    public $selectedPermissions = [];

    #[Computed]
    public function roles()
    {
        return Role::with('permissions')->get();
    }

    #[Computed]
    public function permissions()
    {
        return Permission::all();
    }

    public function openModal($roleId = null)
    {
        if ($roleId) {
            $role = Role::find($roleId);
            $this->editingRole = $roleId;
            $this->roleName = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        } else {
            $this->editingRole = null;
            $this->roleName = '';
            $this->selectedPermissions = [];
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->editingRole = null;
        $this->roleName = '';
        $this->selectedPermissions = [];
    }

    public function save()
    {
        $this->validate([
            'roleName' => 'required|string|min:2|max:255',
        ]);

        if ($this->editingRole) {
            $role = Role::find($this->editingRole);
            $role->update(['name' => $this->roleName]);
        } else {
            $role = Role::create(['name' => $this->roleName]);
        }

        $role->syncPermissions($this->selectedPermissions);

        $this->closeModal();
    }

    public function deleteRole($roleId)
    {
        if ($roleId !== 1) { // Don't delete manager role
            Role::find($roleId)->delete();
        }
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <div>
            <flux:heading size="xl">Manage Roles</flux:heading>
            <p class="text-zinc-600 dark:text-zinc-400">Configure roles and permissions</p>
        </div>
        <flux:button wire:click="openModal" variant="primary">
            <flux:icon icon="plus" />
            Add Role
        </flux:button>
    </div>

    <!-- Roles Table -->
    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Role Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Permissions</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->roles as $role)
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm">{{ $role->name }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex flex-wrap gap-2">
                                @forelse($role->permissions as $permission)
                                    <flux:badge>{{ $permission->name }}</flux:badge>
                                @empty
                                    <span class="text-zinc-500 dark:text-zinc-400">No permissions</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <flux:button wire:click="openModal({{ $role->id }})" size="sm" variant="subtle">
                                Edit
                            </flux:button>
                            @if($role->id !== 1)
                                <flux:button wire:click="deleteRole({{ $role->id }})" size="sm" variant="danger">
                                    Delete
                                </flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-sm text-zinc-500">No roles found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <flux:modal wire:model="showModal">
        <flux:heading>{{ $editingRole ? 'Edit Role' : 'Create Role' }}</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Role Name</flux:label>
                <flux:input type="text" wire:model="roleName" placeholder="e.g., Manager, Employee" />
                <flux:error name="roleName" />
            </flux:field>

            <flux:field>
                <flux:label>Permissions</flux:label>
                <div class="mt-2 space-y-2">
                    @forelse($this->permissions as $permission)
                        <label class="flex items-center space-x-2">
                            <flux:checkbox wire:model="selectedPermissions" value="{{ $permission->id }}" />
                            <span class="text-sm">{{ $permission->name }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-zinc-500">No permissions available</p>
                    @endforelse
                </div>
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">Save Role</flux:button>
        </div>
    </flux:modal>
</div>
