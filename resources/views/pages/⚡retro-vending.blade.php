<?php

use App\Actions\ProcessPurchase;
use App\Enums\SlotCategory;
use App\Models\Machine;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.retro')] class extends Component
{
    #[Locked]
    public ?int $selectedMachineId = null;

    #[Locked]
    public ?int $selectedSlotId = null;

    public bool $showConfirmModal = false;

    public ?string $resultMessage = null;
    public bool $resultSuccess = false;

    // Retro-specific properties
    public string $gameState = 'select_machine'; // select_machine, select_slot, confirm, dispensing, result

    #[Computed]
    public function balance(): int
    {
        return Auth::user()->balance?->current_balance ?? 0;
    }

    #[Computed]
    public function roleLimit(): ?\App\Models\RoleLimit
    {
        return Auth::user()->roleLimit();
    }

    #[Computed]
    public function todayUsage(): array
    {
        $userId = Auth::id();

        return [
            SlotCategory::Juice->value  => Transaction::todayCategoryCount($userId, SlotCategory::Juice),
            SlotCategory::Meal->value   => Transaction::todayCategoryCount($userId, SlotCategory::Meal),
            SlotCategory::Snack->value  => Transaction::todayCategoryCount($userId, SlotCategory::Snack),
        ];
    }

    #[Computed]
    public function machines(): \Illuminate\Database\Eloquent\Collection
    {
        return Machine::where('is_active', true)->orderBy('code')->get();
    }

    #[Computed]
    public function machineSlots(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->selectedMachineId) {
            return \App\Models\MachineSlot::whereRaw('1 = 0')->get();
        }

        return \App\Models\MachineSlot::where('machine_id', $this->selectedMachineId)
            ->orderBy('slot_number')
            ->get();
    }

    #[Computed]
    public function selectedSlot(): ?\App\Models\MachineSlot
    {
        if (! $this->selectedSlotId) {
            return null;
        }

        return \App\Models\MachineSlot::find($this->selectedSlotId);
    }

    #[Computed]
    public function recentTransactions(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::user()->transactions()
            ->with('slot')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function selectMachine(int $machineId): void
    {
        $this->selectedMachineId = $machineId;
        $this->selectedSlotId = null;
        $this->resultMessage = null;
        $this->gameState = 'select_slot';
        $this->unsetComputedProperties();
    }

    public function selectSlot(int $slotId): void
    {
        $slot = \App\Models\MachineSlot::find($slotId);

        if (! $slot) {
            return;
        }

        // Check if slot is out of stock
        if ($slot->quantity === 0) {
            $this->resultMessage = 'This item is out of stock.';
            $this->resultSuccess = false;
            $this->gameState = 'result';
            return;
        }

        // Check quota and balance
        $catUsed = $this->todayUsage[$slot->category->value];
        $catLimit = $this->roleLimit?->{"daily_{$slot->category->value}_limit"} ?? PHP_INT_MAX;
        $disabled = $catUsed >= $catLimit || $this->balance < $slot->price;

        if ($disabled) {
            if ($this->balance < $slot->price) {
                $this->resultMessage = 'Insufficient balance.';
            } else {
                $this->resultMessage = "Daily {$slot->category->value} limit reached.";
            }
            $this->resultSuccess = false;
            $this->gameState = 'result';
            return;
        }

        $this->selectedSlotId = $slotId;
        $this->showConfirmModal = true;
        $this->resultMessage = null;
        $this->gameState = 'confirm';
        unset($this->selectedSlot);
    }

    public function purchase(ProcessPurchase $action): void
    {
        $user = Auth::user();
        $slot = $this->selectedSlot;
        $machine = Machine::find($this->selectedMachineId);

        if (! $user->card_number) {
            $this->resultMessage = 'Your account has no card number assigned. Contact an administrator.';
            $this->resultSuccess = false;
            $this->showConfirmModal = false;
            $this->gameState = 'result';

            return;
        }

        // Trigger dispensing animation
        $this->gameState = 'dispensing';

        // Process purchase after a short delay for animation
        $result = $action->handle(
            cardNumber: $user->card_number,
            machineId: $machine->code,
            slotNumber: $slot->slot_number,
            productPrice: $slot->price,
        );

        $this->resultMessage = $result['message'];
        $this->resultSuccess = $result['success'];
        $this->showConfirmModal = false;
        $this->selectedSlotId = null;

        // Show result after animation
        $this->gameState = 'result';

        $this->unsetComputedProperties();
    }

    public function cancelPurchase(): void
    {
        $this->selectedSlotId = null;
        $this->showConfirmModal = false;
        $this->gameState = 'select_slot';
        $this->resultMessage = null;
        unset($this->selectedSlot);
    }

    public function continueToSelection(): void
    {
        $this->resultMessage = null;
        $this->gameState = 'select_slot';
    }

    public function backToMachines(): void
    {
        $this->selectedMachineId = null;
        $this->selectedSlotId = null;
        $this->resultMessage = null;
        $this->gameState = 'select_machine';
        $this->unsetComputedProperties();
    }

    private function unsetComputedProperties(): void
    {
        unset($this->balance, $this->todayUsage, $this->recentTransactions, $this->machineSlots, $this->selectedSlot);
    }
}; ?>

<div x-data="retroVending()" x-init="initRetro()" class="space-y-6">
        {{-- Result Screen --}}
        @if ($gameState === 'result')
            <div class="retro-screen pixel-border p-8 text-center animate-fade-in">
                <div class="text-6xl mb-6">
                    {{ $resultSuccess ? '✓' : '✗' }}
                </div>
                <h2 class="text-2xl mb-4">
                    {{ $resultSuccess ? 'ENJOY!' : 'ERROR' }}
                </h2>
                <p class="font-terminal text-lg mb-8 px-4">
                    {{ $resultMessage }}
                </p>
                <button
                    wire:click="continueToSelection"
                    class="retro-button-primary"
                >
                    CONTINUE
                </button>
            </div>
        @endif

        {{-- Machine Selection Screen --}}
        <div x-show="gameState === 'select_machine'" x-transition class="space-y-6">
            <div class="text-center">
                <h2 class="text-2xl mb-2 animate-cursor-blink">SELECT MACHINE</h2>
                <p class="font-terminal text-sm text-retro-dark">Choose a vending machine to begin</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($this->machines as $machine)
                    <button
                        wire:click="selectMachine({{ $machine->id }})"
                        wire:key="machine-{{ $machine->id }}"
                        class="pixel-border p-6 text-left transition-all hover:scale-105 active:scale-95"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-4xl">🏪</span>
                            @if ($selectedMachineId === $machine->id)
                                <span class="led-indicator led-green animate-led-pulse"></span>
                            @endif
                        </div>
                        <h3 class="text-lg mb-2">{{ $machine->code }}</h3>
                        <p class="font-terminal text-sm text-retro-dark">
                            {{ $machine->location }}
                        </p>
                    </button>
                @empty
                    <div class="col-span-full pixel-border p-8 text-center">
                        <p class="font-terminal text-lg">NO ACTIVE MACHINES AVAILABLE</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Slot Selection Screen --}}
        <div x-show="gameState === 'select_slot'" x-transition class="space-y-6">
            {{-- Machine Info & Back Button --}}
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl mb-1">
                        {{ \App\Models\Machine::find($selectedMachineId)?->code ?? 'MACHINE' }}
                    </h2>
                    <p class="font-terminal text-sm text-retro-dark">SELECT A SLOT</p>
                </div>
                <button
                    wire:click="backToMachines"
                    class="retro-button text-xs"
                >
                    ◄ BACK
                </button>
            </div>

            {{-- Balance & Quota Panel --}}
            <div class="pixel-border bg-retro-light p-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-xs text-retro-dark mb-1">BALANCE</p>
                        <p class="text-2xl">{{ $this->balance }}</p>
                    </div>
                    @foreach ([SlotCategory::Juice, SlotCategory::Meal, SlotCategory::Snack] as $cat)
                        @php
                            $limit = $this->roleLimit?->{"daily_{$cat->value}_limit"} ?? '∞';
                            $used = $this->todayUsage[$cat->value];
                            $atLimit = is_int($limit) && $used >= $limit;
                        @endphp
                        <div>
                            <p class="text-xs text-retro-dark mb-1 uppercase">{{ $cat->value }}</p>
                            <p class="text-2xl {{ $atLimit ? 'text-red-600' : '' }}">
                                {{ $used }}/{{ $limit }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Slot Grid --}}
            @if ($this->machineSlots->isEmpty())
                <div class="pixel-border p-8 text-center">
                    <p class="font-terminal text-lg">NO SLOTS AVAILABLE ON THIS MACHINE</p>
                </div>
            @else
                <div class="grid grid-cols-4 md:grid-cols-5 lg:grid-cols-8 gap-3">
                    @foreach ($this->machineSlots as $slot)
                        @php
                            $catUsed = $this->todayUsage[$slot->category->value];
                            $catLimit = $this->roleLimit?->{"daily_{$slot->category->value}_limit"} ?? PHP_INT_MAX;
                            $disabled = $catUsed >= $catLimit || $this->balance < $slot->price || $slot->quantity === 0;
                        @endphp
                        <button
                            wire:click="{{ $disabled ? '' : "selectSlot({$slot->id})" }}"
                            wire:key="slot-{{ $slot->id }}"
                            @disabled($disabled)
                            class="pixel-border p-3 text-center transition-all relative
                                {{ $disabled
                                    ? 'opacity-50 cursor-not-allowed'
                                    : 'hover:bg-retro-lightest hover:scale-105 active:scale-95'
                                }}
                                {{ $selectedSlotId === $slot->id ? 'bg-retro-light animate-slot-highlight' : '' }}"
                        >
                            {{-- Stock LED --}}
                            <div class="flex justify-center mb-2">
                                @if ($slot->quantity === 0)
                                    <span class="led-indicator stock-out"></span>
                                @elseif ($slot->quantity > 10)
                                    <span class="led-indicator stock-high animate-led-pulse"></span>
                                @elseif ($slot->quantity > 5)
                                    <span class="led-indicator stock-medium"></span>
                                @else
                                    <span class="led-indicator stock-low"></span>
                                @endif
                            </div>

                            {{-- Product Icon --}}
                            <div class="text-3xl mb-2">
                                @if ($slot->category->value === 'juice') 🧃
                                @elseif ($slot->category->value === 'meal') 🍱
                                @else 🍪
                                @endif
                            </div>

                            {{-- Slot Info --}}
                            <p class="text-xs mb-1">{{ str_pad($slot->slot_number, 2, '0', STR_PAD_LEFT) }}</p>
                            <p class="font-terminal text-sm">{{ $slot->price }}p</p>

                            {{-- Out of Stock Overlay --}}
                            @if ($slot->quantity === 0)
                                <div class="absolute inset-0 bg-black/70 flex items-center justify-center">
                                    <span class="text-xs font-terminal text-red-500">SOLD OUT</span>
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Legend --}}
            <div class="pixel-border p-3 bg-retro-light">
                <div class="flex flex-wrap gap-4 justify-center text-xs font-terminal">
                    <div class="flex items-center gap-2">
                        <span class="led-indicator stock-high"></span>
                        <span>STOCK >10</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="led-indicator stock-medium"></span>
                        <span>STOCK 5-10</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="led-indicator stock-low"></span>
                        <span>STOCK &lt;5</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="led-indicator stock-out"></span>
                        <span>OUT OF STOCK</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Confirm Screen --}}
        <div x-show="gameState === 'confirm'" x-transition class="space-y-6">
            <div class="retro-screen pixel-border p-8 text-center">
                <h2 class="text-2xl mb-6 animate-cursor-blink">CONFIRM PURCHASE?</h2>

                @if ($this->selectedSlot)
                    <div class="pixel-border bg-retro-light p-6 mb-6 inline-block">
                        {{-- Product Icon --}}
                        <div class="text-6xl mb-4 animate-product-drop">
                            @if ($this->selectedSlot->category->value === 'juice') 🧃
                            @elseif ($this->selectedSlot->category->value === 'meal') 🍱
                            @else 🍪
                            @endif
                        </div>

                        {{-- Product Details --}}
                        <div class="space-y-2 text-left font-terminal">
                            <div class="flex justify-between">
                                <span class="text-retro-dark">MACHINE:</span>
                                <span class="font-bold">{{ \App\Models\Machine::find($selectedMachineId)?->code }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-retro-dark">SLOT:</span>
                                <span class="font-bold">{{ $this->selectedSlot->slot_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-retro-dark">ITEM:</span>
                                <span class="font-bold uppercase">{{ $this->selectedSlot->category->value }}</span>
                            </div>
                            <div class="flex justify-between border-t-2 border-retro-dark pt-2">
                                <span class="text-retro-dark">COST:</span>
                                <span class="text-2xl font-bold">{{ $this->selectedSlot->price }} PTS</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-retro-dark">BALANCE AFTER:</span>
                                <span class="font-bold">{{ $this->balance - $this->selectedSlot->price }} PTS</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Buttons --}}
                <div class="flex gap-4 justify-center">
                    <button
                        wire:click="purchase"
                        class="retro-button-primary"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="purchase">A: YES</span>
                        <span wire:loading wire:target="purchase">DISPENSING...</span>
                    </button>
                    <button
                        wire:click="cancelPurchase"
                        class="retro-button"
                    >
                        B: NO
                    </button>
                </div>
            </div>
        </div>

        {{-- Dispensing Animation Screen --}}
        <div x-show="gameState === 'dispensing'" x-transition class="space-y-6">
            <div class="retro-screen pixel-border p-8 text-center">
                <h2 class="text-2xl mb-8 animate-cursor-blink">DISPENSING...</h2>

                {{-- Animation Container --}}
                <div class="relative h-64 mb-6">
                    <div class="absolute inset-0 flex items-center justify-center">
                        {{-- Product Icon with Animation --}}
                        <div class="text-8xl animate-machine-shake">
                            @if ($this->selectedSlot?->category->value === 'juice') 🧃
                            @elseif ($this->selectedSlot?->category->value === 'meal') 🍱
                            @else 🍪
                            @endif
                        </div>
                    </div>

                    {{-- Animated Coil/Dispenser --}}
                    <div class="absolute bottom-0 left-0 right-0 h-2 bg-retro-dark"></div>
                </div>

                <p class="font-terminal text-lg text-retro-dark animate-pulse">
                    Please wait...
                </p>
            </div>
        </div>

        {{-- Recent Transactions (always visible at bottom) --}}
        @if ($gameState !== 'dispensing' && $gameState !== 'confirm')
            <div class="pixel-border bg-retro-light p-4">
                <h3 class="text-sm mb-3 font-terminal">RECENT PURCHASES</h3>

                @if ($this->recentTransactions->isEmpty())
                    <p class="font-terminal text-sm text-retro-dark text-center py-4">
                        No purchases yet today
                    </p>
                @else
                    <div class="space-y-2">
                        @foreach ($this->recentTransactions as $tx)
                            <div
                                wire:key="tx-{{ $tx->id }}"
                                class="flex items-center justify-between text-sm font-terminal border-b border-retro-dark pb-2 last:border-0"
                            >
                                <div class="flex items-center gap-2">
                                    @if ($tx->status === \App\Enums\TransactionStatus::Success)
                                        <span class="text-green-600">✓</span>
                                    @else
                                        <span class="text-red-600">✗</span>
                                    @endif
                                    <span class="capitalize">{{ $tx->slot?->category->value ?? '—' }}</span>
                                    <span class="text-xs text-retro-dark">{{ $tx->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-right">
                                    @if ($tx->status === \App\Enums\TransactionStatus::Success)
                                        <span class="font-bold">-{{ $tx->points_deducted }} PTS</span>
                                    @else
                                        <span class="text-xs text-retro-dark">{{ $tx->notes }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

</div>
