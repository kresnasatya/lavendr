<?php

use App\Enums\TransactionStatus;
use App\Models\Machine;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function stats(): array
    {
        return [
            'totalEmployees'   => User::role('employee')->count(),
            'totalMachines'    => Machine::count(),
            'totalTransactions' => Transaction::count(),
            'totalPointsSpent' => Transaction::where('status', TransactionStatus::Success)->sum('points_deducted'),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">Admin Dashboard</flux:heading>
        <flux:text>Manage your vending machine system</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Total Employees</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $this->stats['totalEmployees'] }}</p>
                </div>
                <flux:icon icon="users" class="h-8 w-8 text-blue-500" />
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Total Machines</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $this->stats['totalMachines'] }}</p>
                </div>
                <flux:icon icon="square-3-stack-3d" class="h-8 w-8 text-green-500" />
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Total Transactions</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $this->stats['totalTransactions'] }}</p>
                </div>
                <flux:icon icon="shopping-cart" class="h-8 w-8 text-purple-500" />
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Points Spent</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ number_format($this->stats['totalPointsSpent']) }} pts</p>
                </div>
                <flux:icon icon="banknotes" class="h-8 w-8 text-amber-500" />
            </div>
        </div>
    </div>

    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
        <flux:heading size="lg">Admin Controls</flux:heading>
        <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <flux:button href="{{ route('admin.employees') }}" variant="ghost" class="justify-start">
                <flux:icon icon="users" />
                Manage Employees
            </flux:button>
            <flux:button href="{{ route('admin.machines') }}" variant="ghost" class="justify-start">
                <flux:icon icon="square-3-stack-3d" />
                Manage Machines
            </flux:button>
            <flux:button href="{{ route('admin.slots') }}" variant="ghost" class="justify-start">
                <flux:icon icon="archive-box" />
                Manage Slots
            </flux:button>
            <flux:button href="{{ route('admin.balances') }}" variant="ghost" class="justify-start">
                <flux:icon icon="wallet" />
                Manage Balances
            </flux:button>
            <flux:button href="{{ route('admin.reports') }}" variant="ghost" class="justify-start">
                <flux:icon icon="chart-bar" />
                View Reports
            </flux:button>
        </div>
    </div>
</div>
