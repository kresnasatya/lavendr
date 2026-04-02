<?php

use App\Actions\ProcessPurchase;
use App\Enums\SlotCategory;
use App\Models\Machine;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?int $selectedMachineId = null;

    #[Locked]
    public ?int $selectedSlotId = null;

    public bool $showConfirmModal = false;

    public ?string $resultMessage = null;
    public bool $resultSuccess = false;

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
            return collect();
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
        $this->unsetComputedProperties();
    }

    public function selectSlot(int $slotId): void
    {
        $this->selectedSlotId = $slotId;
        $this->showConfirmModal = true;
        $this->resultMessage = null;
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

            return;
        }

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

        $this->unsetComputedProperties();
    }

    private function unsetComputedProperties(): void
    {
        unset($this->balance, $this->todayUsage, $this->recentTransactions, $this->machineSlots, $this->selectedSlot);
    }
}; ?>

<div class="space-y-6">
    {{-- Page header --}}
    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <flux:heading size="xl">My Dashboard</flux:heading>
        <flux:text>Welcome back, {{ Auth::user()->name }}</flux:text>
    </div>

    {{-- Result flash --}}
    @if ($resultMessage)
        <flux:callout
            :variant="$resultSuccess ? 'success' : 'danger'"
            :icon="$resultSuccess ? 'check-circle' : 'x-circle'"
        >
            <flux:callout.heading>{{ $resultSuccess ? 'Purchase successful' : 'Purchase failed' }}</flux:callout.heading>
            <flux:callout.text>{{ $resultMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Balance + category usage --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Current Balance</flux:text>
            <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ $this->balance }} pts</p>
        </div>

        @foreach ([SlotCategory::Juice, SlotCategory::Meal, SlotCategory::Snack] as $cat)
            @php
                $limit = $this->roleLimit?->{"daily_{$cat->value}_limit"} ?? '∞';
                $used  = $this->todayUsage[$cat->value];
                $atLimit = is_int($limit) && $used >= $limit;
            @endphp
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text size="sm" class="capitalize text-zinc-500 dark:text-zinc-400">{{ $cat->value }} today</flux:text>
                <p class="mt-1 text-3xl font-bold {{ $atLimit ? 'text-red-500' : 'text-zinc-900 dark:text-white' }}">
                    {{ $used }} / {{ $limit }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Purchase simulation --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Step 1: Pick machine --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">1. Select a Machine</flux:heading>
            <div class="space-y-2">
                @forelse ($this->machines as $machine)
                    <button
                        wire:click="selectMachine({{ $machine->id }})"
                        wire:key="machine-{{ $machine->id }}"
                        class="flex w-full items-center justify-between rounded-lg border px-4 py-3 text-left transition-colors
                            {{ $selectedMachineId === $machine->id
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-950'
                                : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
                    >
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $machine->code }}</p>
                            <p class="text-sm text-zinc-500">{{ $machine->location }}</p>
                        </div>
                        @if ($selectedMachineId === $machine->id)
                            <flux:icon icon="check-circle" class="h-5 w-5 text-blue-500" />
                        @endif
                    </button>
                @empty
                    <flux:text class="text-zinc-500">No active machines available.</flux:text>
                @endforelse
            </div>
        </div>

        {{-- Step 2: Pick slot --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">2. Select a Slot</flux:heading>

            @if (! $selectedMachineId)
                <flux:text class="text-zinc-400 dark:text-zinc-500">Select a machine first.</flux:text>
            @elseif ($this->machineSlots->isEmpty())
                <flux:text class="text-zinc-400 dark:text-zinc-500">No slots available on this machine.</flux:text>
            @else
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($this->machineSlots as $slot)
                        @php
                            $catUsed  = $this->todayUsage[$slot->category->value];
                            $catLimit = $this->roleLimit?->{"daily_{$slot->category->value}_limit"} ?? PHP_INT_MAX;
                            $disabled = $catUsed >= $catLimit || $this->balance < $slot->price;
                        @endphp
                        <button
                            wire:click="{{ $disabled ? '' : "selectSlot({$slot->id})" }}"
                            wire:key="slot-{{ $slot->id }}"
                            @disabled($disabled)
                            class="rounded-lg border p-3 text-left transition-colors
                                {{ $disabled
                                    ? 'cursor-not-allowed border-zinc-100 bg-zinc-50 opacity-50 dark:border-zinc-800 dark:bg-zinc-900'
                                    : 'border-zinc-200 hover:border-blue-400 hover:bg-blue-50 dark:border-zinc-700 dark:hover:border-blue-500 dark:hover:bg-blue-950' }}"
                        >
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Slot {{ $slot->slot_number }}</p>
                            <p class="mt-0.5 capitalize font-medium text-zinc-900 dark:text-white">{{ $slot->category->value }}</p>
                            <p class="text-sm text-zinc-500">{{ $slot->price }} pts</p>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Confirm purchase modal --}}
    <flux:modal wire:model="showConfirmModal" class="max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">Confirm Purchase</flux:heading>

            @if ($this->selectedSlot)
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-500">Machine</flux:text>
                        <flux:text class="font-medium">{{ Machine::find($selectedMachineId)?->code }}</flux:text>
                    </div>
                    <div class="mt-2 flex justify-between text-sm">
                        <flux:text class="text-zinc-500">Slot</flux:text>
                        <flux:text class="font-medium">{{ $this->selectedSlot->slot_number }}</flux:text>
                    </div>
                    <div class="mt-2 flex justify-between text-sm">
                        <flux:text class="text-zinc-500">Category</flux:text>
                        <flux:text class="font-medium capitalize">{{ $this->selectedSlot->category->value }}</flux:text>
                    </div>
                    <div class="mt-2 flex justify-between text-sm">
                        <flux:text class="text-zinc-500">Cost</flux:text>
                        <flux:text class="font-bold text-zinc-900 dark:text-white">{{ $this->selectedSlot->price }} pts</flux:text>
                    </div>
                    <div class="mt-2 flex justify-between border-t border-zinc-200 pt-2 text-sm dark:border-zinc-700">
                        <flux:text class="text-zinc-500">Balance after</flux:text>
                        <flux:text class="font-bold text-zinc-900 dark:text-white">{{ $this->balance - $this->selectedSlot->price }} pts</flux:text>
                    </div>
                </div>
            @endif

            <div class="flex gap-2">
                <flux:button wire:click="purchase" variant="primary" class="flex-1" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="purchase">Confirm</span>
                    <span wire:loading wire:target="purchase">Processing…</span>
                </flux:button>
                <flux:button wire:click="$set('showConfirmModal', false)" variant="ghost">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Recent transactions --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <flux:heading size="lg">Recent Purchases</flux:heading>
        </div>

        @if ($this->recentTransactions->isEmpty())
            <div class="px-5 py-8 text-center">
                <flux:text class="text-zinc-400">No purchases yet.</flux:text>
            </div>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->recentTransactions as $tx)
                    <div wire:key="tx-{{ $tx->id }}" class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            @if ($tx->status === TransactionStatus::Success)
                                <flux:icon icon="check-circle" class="h-5 w-5 shrink-0 text-green-500" />
                            @else
                                <flux:icon icon="x-circle" class="h-5 w-5 shrink-0 text-red-400" />
                            @endif
                            <div>
                                <p class="text-sm font-medium capitalize text-zinc-900 dark:text-white">
                                    {{ $tx->slot?->category->value ?? '—' }}
                                </p>
                                <p class="text-xs text-zinc-400">{{ $tx->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if ($tx->status === TransactionStatus::Success)
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">-{{ $tx->points_deducted }} pts</p>
                            @else
                                <p class="text-xs text-zinc-400">{{ $tx->notes }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
