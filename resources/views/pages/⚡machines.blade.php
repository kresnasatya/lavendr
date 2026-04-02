<?php

use App\Models\Machine;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $showModal = false;
    public ?int $editingMachine = null;
    public string $code = '';
    public string $location = '';
    public bool $isActive = true;
    public string $searchTerm = '';

    #[Computed]
    public function machines(): \Illuminate\Database\Eloquent\Collection
    {
        return Machine::with('slots')
            ->where(function ($q) {
                $q->where('code', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('location', 'like', '%'.$this->searchTerm.'%');
            })
            ->orderBy('code')
            ->get();
    }

    public function openModal(?int $machineId = null): void
    {
        if ($machineId) {
            $machine = Machine::findOrFail($machineId);
            $this->editingMachine = $machineId;
            $this->code = $machine->code;
            $this->location = $machine->location;
            $this->isActive = $machine->is_active;
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
            'code'     => 'required|string|unique:machines,code,'.($this->editingMachine ?? 'NULL'),
            'location' => 'required|string|min:3|max:255',
        ]);

        if ($this->editingMachine) {
            Machine::findOrFail($this->editingMachine)->update([
                'code'       => $this->code,
                'location'   => $this->location,
                'is_active'  => $this->isActive,
            ]);
        } else {
            Machine::create([
                'code'       => $this->code,
                'location'   => $this->location,
                'is_active'  => $this->isActive,
            ]);
        }

        unset($this->machines);
        $this->closeModal();
    }

    public function deleteMachine(int $machineId): void
    {
        Machine::findOrFail($machineId)->delete();
        unset($this->machines);
    }

    public function toggleActive(int $machineId): void
    {
        $machine = Machine::findOrFail($machineId);
        $machine->update(['is_active' => ! $machine->is_active]);
        unset($this->machines);
    }

    private function resetForm(): void
    {
        $this->editingMachine = null;
        $this->code = '';
        $this->location = '';
        $this->isActive = true;
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <div>
            <flux:heading size="xl">Manage Machines</flux:heading>
            <flux:text>Add and configure vending machines</flux:text>
        </div>
        <flux:button wire:click="openModal" variant="primary" icon="plus">
            Add Machine
        </flux:button>
    </div>

    <flux:field>
        <flux:label>Search</flux:label>
        <flux:input wire:model.live="searchTerm" type="text" placeholder="Code or location…" />
    </flux:field>

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
                @forelse ($this->machines as $machine)
                    <tr wire:key="machine-{{ $machine->id }}" class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 font-mono text-sm">{{ $machine->code }}</td>
                        <td class="px-6 py-4 text-sm">{{ $machine->location }}</td>
                        <td class="px-6 py-4 text-center text-sm">
                            <flux:badge>{{ $machine->slots->count() }} slots</flux:badge>
                        </td>
                        <td class="px-6 py-4 text-center text-sm">
                            @if ($machine->is_active)
                                <flux:badge variant="success">Active</flux:badge>
                            @else
                                <flux:badge variant="danger">Inactive</flux:badge>
                            @endif
                        </td>
                        <td class="space-x-1 px-6 py-4 text-right text-sm">
                            <flux:button wire:click="openModal({{ $machine->id }})" size="sm" variant="subtle">Edit</flux:button>
                            <flux:button wire:click="toggleActive({{ $machine->id }})" size="sm" variant="ghost">
                                {{ $machine->is_active ? 'Disable' : 'Enable' }}
                            </flux:button>
                            <flux:button wire:click="deleteMachine({{ $machine->id }})" size="sm" variant="danger">Delete</flux:button>
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

    <flux:modal wire:model="showModal" class="max-w-md">
        <flux:heading>{{ $editingMachine ? 'Edit Machine' : 'Add Machine' }}</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Code</flux:label>
                <flux:input type="text" wire:model="code" placeholder="e.g., VM-001" />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>Location</flux:label>
                <flux:input type="text" wire:model="location" placeholder="e.g., Building A, Floor 2" />
                <flux:error name="location" />
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
