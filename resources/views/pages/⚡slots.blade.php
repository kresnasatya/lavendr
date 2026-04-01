<?php

use Livewire\Component;
use Livewire\Attributes\{Validate, Computed};
use App\Models\MachineSlot;
use App\Models\Machine;

new class extends Component
{
    public $showModal = false;
    public $editingSlot = null;
    public $machineId = '';
    public $slotNumber = '';
    public $category = '';
    public $price = 0;
    public $quantity = 0;
    public $selectedMachine = '';

    #[Computed]
    public function machines()
    {
        return Machine::all();
    }

    #[Computed]
    public function slots()
    {
        $query = MachineSlot::with('machine');
        if ($this->selectedMachine) {
            $query->where('machine_id', $this->selectedMachine);
        }
        return $query->get();
    }

    public function openModal($slotId = null)
    {
        if ($slotId) {
            $slot = MachineSlot::find($slotId);
            $this->editingSlot = $slotId;
            $this->machineId = $slot->machine_id;
            $this->slotNumber = $slot->slot_number;
            $this->category = $slot->category;
            $this->price = $slot->price;
            $this->quantity = $slot->quantity;
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
        $this->editingSlot = null;
        $this->machineId = '';
        $this->slotNumber = '';
        $this->category = '';
        $this->price = 0;
        $this->quantity = 0;
    }

    public function save()
    {
        $this->validate([
            'machineId' => 'required|exists:machines,id',
            'slotNumber' => 'required|string|max:10',
            'category' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        if ($this->editingSlot) {
            $slot = MachineSlot::find($this->editingSlot);
            $slot->update([
                'machine_id' => $this->machineId,
                'slot_number' => $this->slotNumber,
                'category' => $this->category,
                'price' => $this->price,
                'quantity' => $this->quantity,
            ]);
        } else {
            MachineSlot::create([
                'machine_id' => $this->machineId,
                'slot_number' => $this->slotNumber,
                'category' => $this->category,
                'price' => $this->price,
                'quantity' => $this->quantity,
            ]);
        }

        $this->closeModal();
    }

    public function deleteSlot($slotId)
    {
        MachineSlot::find($slotId)->delete();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <div>
            <flux:heading size="xl">Manage Slots</flux:heading>
            <p class="text-zinc-600 dark:text-zinc-400">Configure machine slots and categories</p>
        </div>
        <flux:button wire:click="openModal" variant="primary">
            <flux:icon icon="plus" />
            Add Slot
        </flux:button>
    </div>

    <!-- Filter -->
    <flux:field>
        <flux:label>Filter by Machine</flux:label>
        <flux:select wire:model.live="selectedMachine">
            <option value="">All Machines</option>
            @foreach($this->machines as $machine)
                <option value="{{ $machine->id }}">{{ $machine->code }} - {{ $machine->location }}</option>
            @endforeach
        </flux:select>
    </flux:field>

    <!-- Slots Table -->
    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Machine</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Slot</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Category</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Price</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Qty</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->slots as $slot)
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm font-mono">{{ $slot->machine->code }}</td>
                        <td class="px-6 py-4 text-sm">{{ $slot->slot_number }}</td>
                        <td class="px-6 py-4 text-sm">{{ $slot->category }}</td>
                        <td class="px-6 py-4 text-right text-sm">${{ number_format($slot->price / 100, 2) }}</td>
                        <td class="px-6 py-4 text-right text-sm">{{ $slot->quantity }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <flux:button wire:click="openModal({{ $slot->id }})" size="sm" variant="subtle">
                                Edit
                            </flux:button>
                            <flux:button wire:click="deleteSlot({{ $slot->id }})" size="sm" variant="danger">
                                Delete
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-500">No slots found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <flux:modal wire:model="showModal">
        <flux:heading>{{ $editingSlot ? 'Edit Slot' : 'Add Slot' }}</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Machine</flux:label>
                <flux:select wire:model="machineId">
                    <option value="">Select a machine...</option>
                    @foreach($this->machines as $machine)
                        <option value="{{ $machine->id }}">{{ $machine->code }} - {{ $machine->location }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="machineId" />
            </flux:field>

            <flux:field>
                <flux:label>Slot Number</flux:label>
                <flux:input type="text" wire:model="slotNumber" placeholder="e.g., A1, B2" />
                <flux:error name="slotNumber" />
            </flux:field>

            <flux:field>
                <flux:label>Category</flux:label>
                <flux:input type="text" wire:model="category" placeholder="e.g., Snacks, Beverages" />
                <flux:error name="category" />
            </flux:field>

            <flux:field>
                <flux:label>Price (in cents)</flux:label>
                <flux:input type="number" wire:model="price" placeholder="100" />
                <flux:error name="price" />
            </flux:field>

            <flux:field>
                <flux:label>Quantity</flux:label>
                <flux:input type="number" wire:model="quantity" placeholder="10" />
                <flux:error name="quantity" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">Save Slot</flux:button>
        </div>
    </flux:modal>
</div>
