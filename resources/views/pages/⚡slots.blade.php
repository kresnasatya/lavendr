<?php

use App\Enums\SlotCategory;
use App\Models\Machine;
use App\Models\MachineSlot;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $showModal = false;
    public ?int $editingSlot = null;
    public ?int $machineId = null;
    public int $slotNumber = 1;
    public string $category = '';
    public int $price = 0;
    public int $quantity = 0;
    public ?int $selectedMachine = null;

    #[Computed]
    public function machines(): \Illuminate\Database\Eloquent\Collection
    {
        return Machine::orderBy('code')->get();
    }

    #[Computed]
    public function machineSlots(): \Illuminate\Database\Eloquent\Collection
    {
        $query = MachineSlot::with('machine')->orderBy('machine_id')->orderBy('slot_number');

        if ($this->selectedMachine) {
            $query->where('machine_id', $this->selectedMachine);
        }

        return $query->get();
    }

    public function openModal(?int $slotId = null): void
    {
        if ($slotId) {
            $slot = MachineSlot::findOrFail($slotId);
            $this->editingSlot = $slotId;
            $this->machineId   = $slot->machine_id;
            $this->slotNumber  = $slot->slot_number;
            $this->category    = $slot->category->value;
            $this->price       = $slot->price;
            $this->quantity    = $slot->quantity;
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
            'machineId'  => 'required|exists:machines,id',
            'slotNumber' => 'required|integer|min:1',
            'category'   => 'required|in:juice,meal,snack',
            'price'      => 'required|integer|min:1',
            'quantity'   => 'required|integer|min:0',
        ]);

        if ($this->editingSlot) {
            MachineSlot::findOrFail($this->editingSlot)->update([
                'machine_id'  => $this->machineId,
                'slot_number' => $this->slotNumber,
                'category'    => $this->category,
                'price'       => $this->price,
                'quantity'    => $this->quantity,
            ]);
        } else {
            MachineSlot::create([
                'machine_id'  => $this->machineId,
                'slot_number' => $this->slotNumber,
                'category'    => $this->category,
                'price'       => $this->price,
                'quantity'    => $this->quantity,
            ]);
        }

        unset($this->machineSlots);
        $this->closeModal();
    }

    public function deleteSlot(int $slotId): void
    {
        MachineSlot::findOrFail($slotId)->delete();
        unset($this->machineSlots);
    }

    private function resetForm(): void
    {
        $this->editingSlot = null;
        $this->machineId   = null;
        $this->slotNumber  = 1;
        $this->category    = '';
        $this->price       = 0;
        $this->quantity    = 0;
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <div>
            <flux:heading size="xl">Manage Slots</flux:heading>
            <flux:text>Configure machine slots and product categories</flux:text>
        </div>
        <flux:button wire:click="openModal" variant="primary" icon="plus">
            Add Slot
        </flux:button>
    </div>

    <flux:field>
        <flux:label>Filter by Machine</flux:label>
        <flux:select wire:model.live="selectedMachine">
            <flux:select.option value="">All Machines</flux:select.option>
            @foreach ($this->machines as $machine)
                <flux:select.option value="{{ $machine->id }}">{{ $machine->code }} — {{ $machine->location }}</flux:select.option>
            @endforeach
        </flux:select>
    </flux:field>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Machine</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Slot #</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Category</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Price</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Qty</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->machineSlots as $slot)
                    <tr wire:key="slot-{{ $slot->id }}" class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 font-mono text-sm">{{ $slot->machine->code }}</td>
                        <td class="px-6 py-4 text-center text-sm">{{ $slot->slot_number }}</td>
                        <td class="px-6 py-4 text-sm capitalize">{{ $slot->category->value }}</td>
                        <td class="px-6 py-4 text-right text-sm font-semibold">{{ $slot->price }} pts</td>
                        <td class="px-6 py-4 text-right text-sm">{{ $slot->quantity }}</td>
                        <td class="space-x-1 px-6 py-4 text-right text-sm">
                            <flux:button wire:click="openModal({{ $slot->id }})" size="sm" variant="subtle">Edit</flux:button>
                            <flux:button wire:click="deleteSlot({{ $slot->id }})" size="sm" variant="danger">Delete</flux:button>
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

    <flux:modal wire:model="showModal" class="max-w-md">
        <flux:heading>{{ $editingSlot ? 'Edit Slot' : 'Add Slot' }}</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Machine</flux:label>
                <flux:select wire:model="machineId">
                    <flux:select.option value="">Select a machine…</flux:select.option>
                    @foreach ($this->machines as $machine)
                        <flux:select.option value="{{ $machine->id }}">{{ $machine->code }} — {{ $machine->location }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="machineId" />
            </flux:field>

            <flux:field>
                <flux:label>Slot Number</flux:label>
                <flux:input type="number" wire:model="slotNumber" min="1" />
                <flux:error name="slotNumber" />
            </flux:field>

            <flux:field>
                <flux:label>Category</flux:label>
                <flux:select wire:model="category">
                    <flux:select.option value="">Select a category…</flux:select.option>
                    @foreach (SlotCategory::cases() as $cat)
                        <flux:select.option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="category" />
            </flux:field>

            <flux:field>
                <flux:label>Price (pts)</flux:label>
                <flux:input type="number" wire:model="price" min="1" />
                <flux:error name="price" />
            </flux:field>

            <flux:field>
                <flux:label>Quantity</flux:label>
                <flux:input type="number" wire:model="quantity" min="0" />
                <flux:error name="quantity" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">Save</flux:button>
        </div>
    </flux:modal>
</div>
