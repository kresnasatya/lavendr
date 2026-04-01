<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Machine;
use App\Models\Transaction;

new class extends Component
{
    public function getStats()
    {
        return [
            'totalEmployees' => User::role('employee')->count(),
            'totalMachines' => Machine::count(),
            'totalTransactions' => Transaction::count(),
            'totalRevenue' => Transaction::sum('price'),
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">Admin Dashboard</flux:heading>
        <p class="text-zinc-600 dark:text-zinc-400">Manage your vending machine system</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Total Employees -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Employees</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $this->getStats()['totalEmployees'] }}</p>
                </div>
                <flux:icon icon="users" class="h-8 w-8 text-blue-500" />
            </div>
        </div>

        <!-- Total Machines -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Machines</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $this->getStats()['totalMachines'] }}</p>
                </div>
                <flux:icon icon="square-3-stack-3d" class="h-8 w-8 text-green-500" />
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Transactions</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $this->getStats()['totalTransactions'] }}</p>
                </div>
                <flux:icon icon="shopping-cart" class="h-8 w-8 text-purple-500" />
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Revenue</p>
                    <p class="mt-2 text-3xl font-semibold">${{ number_format($this->getStats()['totalRevenue'] / 100, 2) }}</p>
                </div>
                <flux:icon icon="banknotes" class="h-8 w-8 text-amber-500" />
            </div>
        </div>
    </div>

    <!-- Admin Menu -->
    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
        <flux:heading size="lg">Admin Controls</flux:heading>
        <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <flux:button href="{{ route('admin.roles') }}" variant="ghost" class="justify-start">
                <flux:icon icon="key" />
                Manage Roles
            </flux:button>
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
