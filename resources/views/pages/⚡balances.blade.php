<?php

use App\Models\EmployeeBalance;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $showRechargeModal = false;
    public ?int $selectedEmployee = null;
    public int $rechargeAmount = 0;
    public string $searchTerm = '';

    #[Computed]
    public function employees(): \Illuminate\Database\Eloquent\Collection
    {
        return User::role('employee')
            ->with('balance')
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$this->searchTerm.'%');
            })
            ->orderBy('name')
            ->get();
    }

    public function openRechargeModal(int $employeeId): void
    {
        $this->selectedEmployee = $employeeId;
        $this->rechargeAmount = 0;
        $this->showRechargeModal = true;
    }

    public function closeRechargeModal(): void
    {
        $this->showRechargeModal = false;
        $this->selectedEmployee = null;
        $this->rechargeAmount = 0;
    }

    public function rechargeBalance(): void
    {
        $this->validate([
            'selectedEmployee' => 'required|exists:users,id',
            'rechargeAmount'   => 'required|integer|min:1',
        ]);

        EmployeeBalance::where('user_id', $this->selectedEmployee)
            ->increment('current_balance', $this->rechargeAmount);

        unset($this->employees);
        $this->closeRechargeModal();
    }

    public function resetBalance(int $employeeId): void
    {
        $balance = EmployeeBalance::where('user_id', $employeeId)->first();

        if ($balance) {
            $balance->update(['current_balance' => $balance->daily_quota]);
        }

        unset($this->employees);
    }
}; ?>

<div class="space-y-6">
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">Employee Balances</flux:heading>
        <flux:text>View and top-up employee point balances</flux:text>
    </div>

    <flux:field>
        <flux:label>Search</flux:label>
        <flux:input wire:model.live="searchTerm" type="text" placeholder="Name or email…" />
    </flux:field>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Employee</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Card Number</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Daily Quota</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Current Balance</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Status</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->employees as $employee)
                    <tr wire:key="bal-{{ $employee->id }}" class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $employee->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $employee->email }}</p>
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $employee->card_number ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            {{ $employee->balance?->daily_quota ?? 0 }} pts
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ $employee->balance?->current_balance ?? 0 }} pts
                        </td>
                        <td class="px-6 py-4 text-center text-sm">
                            @php
                                $quota   = $employee->balance?->daily_quota ?? 0;
                                $current = $employee->balance?->current_balance ?? 0;
                            @endphp
                            @if ($current >= $quota && $quota > 0)
                                <flux:badge variant="success">Full</flux:badge>
                            @elseif ($current > 0)
                                <flux:badge variant="warning">Low</flux:badge>
                            @else
                                <flux:badge variant="danger">Empty</flux:badge>
                            @endif
                        </td>
                        <td class="space-x-1 px-6 py-4 text-right text-sm">
                            <flux:button wire:click="openRechargeModal({{ $employee->id }})" size="sm" variant="subtle">
                                Top Up
                            </flux:button>
                            <flux:button wire:click="resetBalance({{ $employee->id }})" size="sm" variant="ghost">
                                Reset
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

    <flux:modal wire:model="showRechargeModal" class="max-w-sm">
        <flux:heading>Top Up Balance</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Amount (pts)</flux:label>
                <flux:input type="number" wire:model="rechargeAmount" min="1" placeholder="e.g., 100" />
                <flux:error name="rechargeAmount" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeRechargeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="rechargeBalance" variant="primary">Top Up</flux:button>
        </div>
    </flux:modal>
</div>
