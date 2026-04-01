<?php

use Livewire\Component;
use Livewire\Attributes\{Validate, Computed};
use App\Models\Machine;

new class extends Component
{
    public $showModal = false;
    public $editingMachine = null;
    public $code = '';
    public $location = '';
    public $isActive = true;
    public $searchTerm = '';

    #[Computed]
    public function machines()
    {
        return Machine::where('code', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('location', 'like', '%' . $this->searchTerm . '%')
            ->get();
    }

    public function openModal($machineId = null)
    {
        if ($machineId) {
            $machine = Machine::find($machineId);
            $this->editingMachine = $machineId;
            $this->code = $machine->code;
            $this->location = $machine->location;
            $this->isActive = $machine->is_active;
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
        $this->editingMachine = null;
        $this->code = '';
        $this->location = '';
        $this->isActive = true;
    }

    public function save()
    {
        $this->validate([
            'code' => 'required|string|unique:machines,code,' . ($this->editingMachine ?? 'NULL'),
            'location' => 'required|string|min:3|max:255',
        ]);

        if ($this->editingMachine) {
            $machine = Machine::find($this->editingMachine);
            $machine->update([
                'code' => $this->code,
                'location' => $this->location,
                'is_active' => $this->isActive,
            ]);
        } else {
            Machine::create([
                'code' => $this->code,
                'location' => $this->location,
                'is_active' => $this->isActive,
            ]);
        }

        $this->closeModal();
    }

    public function deleteMachine($machineId)
    {
        Machine::find($machineId)->delete();
    }

    public function toggleActive($machineId)
    {
        $machine = Machine::find($machineId);
        $machine->update(['is_active' => !$machine->is_active]);
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <div>
            <flux:heading size="xl">Manage Machines</flux:heading>
            <p class="text-zinc-600 dark:text-zinc-400">Add and configure vending machines</p>
        </div>
        <flux:button wire:click="openModal" variant="primary">
            <flux:icon icon="plus" />
            Add Machine
        </flux:button>
    </div>

    <!-- Search -->
    <flux:field>
        <flux:label>Search Machines</flux:label>
        <flux:input wire:model.live="searchTerm" type="text" placeholder="Search by code or location..." />
    </flux:field>

    <!-- Machines Table -->
    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Code</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Location</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Slots</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Status</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->machines as $machine)
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm font-mono">{{ $machine->code }}</td>
                        <td class="px-6 py-4 text-sm">{{ $machine->location }}</td>
                        <td class="px-6 py-4 text-center text-sm">
                            <flux:badge>{{ $machine->slots()->count() }} slots</flux:badge>
                        </td>
                        <td class="px-6 py-4 text-center text-sm">
                            @if($machine->is_active)
                                <flux:badge variant="success">Active</flux:badge>
                            @else
                                <flux:badge variant="danger">Inactive</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <flux:button wire:click="openModal({{ $machine->id }})" size="sm" variant="subtle">
                                Edit
                            </flux:button>
                            <flux:button wire:click="toggleActive({{ $machine->id }})" size="sm" variant="ghost">
                                {{ $machine->is_active ? 'Disable' : 'Enable' }}
                            </flux:button>
                            <flux:button wire:click="deleteMachine({{ $machine->id }})" size="sm" variant="danger">
                                Delete
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-zinc-500">No machines found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <flux:modal wire:model="showModal">
        <flux:heading>{{ $editingMachine ? 'Edit Machine' : 'Add Machine' }}</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Machine Code</flux:label>
                <flux:input type="text" wire:model="code" placeholder="e.g., M001" />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>Location</flux:label>
                <flux:input type="text" wire:model="location" placeholder="e.g., Building A, Floor 2" />
                <flux:error name="location" />
            </flux:field>

            <label class="flex items-center space-x-2">
                <flux:checkbox wire:model="isActive" />
                <span class="text-sm">Active</span>
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">Save Machine</flux:button>
        </div>
    </flux:modal>
</div>
