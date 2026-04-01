<?php

use Livewire\Component;
use Livewire\Attributes\{Validate, Computed};
use App\Models\EmployeeBalance;
use App\Models\User;

new class extends Component
{
    public $showRechargeModal = false;
    public $selectedEmployee = '';
    public $rechargeAmount = 0;
    public $searchTerm = '';

    #[Computed]
    public function employees()
    {
        return User::role('employee')
            ->where('name', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('email', 'like', '%' . $this->searchTerm . '%')
            ->with('balance')
            ->get();
    }

    public function openRechargeModal($employeeId = null)
    {
        if ($employeeId) {
            $this->selectedEmployee = $employeeId;
            $this->rechargeAmount = 0;
        }
        $this->showRechargeModal = true;
    }

    public function closeRechargeModal()
    {
        $this->showRechargeModal = false;
        $this->selectedEmployee = '';
        $this->rechargeAmount = 0;
    }

    public function rechargeBalance()
    {
        $this->validate([
            'selectedEmployee' => 'required|exists:users,id',
            'rechargeAmount' => 'required|integer|min:1',
        ]);

        $balance = EmployeeBalance::where('user_id', $this->selectedEmployee)->first();
        if ($balance) {
            $balance->update([
                'current_balance' => $balance->current_balance + $this->rechargeAmount,
            ]);
        }

        $this->closeRechargeModal();
    }

    public function resetDailyBalance($employeeId)
    {
        $employee = User::find($employeeId);
        $balance = EmployeeBalance::where('user_id', $employeeId)->first();

        if ($balance) {
            $balance->update([
                'current_balance' => $employee->daily_quota,
            ]);
        }
    }

    public function resetAllBalances()
    {
        $employees = User::role('employee')->get();
        foreach ($employees as $employee) {
            $balance = EmployeeBalance::where('user_id', $employee->id)->first();
            if ($balance) {
                $balance->update([
                    'current_balance' => $employee->daily_quota,
                ]);
            }
        }
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <div>
            <flux:heading size="xl">Manage Employee Balances</flux:heading>
            <p class="text-zinc-600 dark:text-zinc-400">Recharge balances and manage daily quotas</p>
        </div>
        <flux:button icon="arrow-path" wire:click="resetAllBalances">
            Reset All Daily Balances
        </flux:button>
    </div>

    <!-- Search -->
    <flux:field>
        <flux:label>Search Employees</flux:label>
        <flux:input wire:model.live="searchTerm" type="text" placeholder="Search by name or email..." />
    </flux:field>

    <!-- Balances Table -->
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
                @forelse($this->employees as $employee)
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm">
                            <div>
                                <p class="font-medium">{{ $employee->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $employee->email }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-mono">{{ $employee->card_number }}</td>
                        <td class="px-6 py-4 text-right text-sm">${{ number_format($employee->daily_quota / 100, 2) }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <span class="font-semibold">
                                ${{ number_format(($employee->balance->current_balance ?? 0) / 100, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm">
                            @if(($employee->balance->current_balance ?? 0) >= $employee->daily_quota)
                                <flux:badge variant="success">Full</flux:badge>
                            @elseif(($employee->balance->current_balance ?? 0) > 0)
                                <flux:badge variant="warning">Low</flux:badge>
                            @else
                                <flux:badge variant="danger">Empty</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <flux:button wire:click="openRechargeModal({{ $employee->id }})" size="sm" variant="subtle">
                                Recharge
                            </flux:button>
                            <flux:button wire:click="resetDailyBalance({{ $employee->id }})" size="sm" variant="ghost">
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

    <!-- Recharge Modal -->
    <flux:modal wire:model="showRechargeModal">
        <flux:heading>Recharge Employee Balance</flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Recharge Amount (in cents)</flux:label>
                <flux:input type="number" wire:model="rechargeAmount" placeholder="e.g., 1000" />
                <flux:error name="rechargeAmount" />
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Amount: ${{ $rechargeAmount ? number_format($rechargeAmount / 100, 2) : '0.00' }}
                </p>
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeRechargeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="rechargeBalance" variant="primary">Recharge</flux:button>
        </div>
    </flux:modal>
</div>
