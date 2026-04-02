<?php

use App\Models\RechargeSetting;
use App\Models\RoleLimit;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    #[Computed]
    public function stats(): array
    {
        return [
            'totalRoles'      => Role::count(),
            'rolesWithLimits' => RoleLimit::count(),
            'rolesWithRecharge' => RechargeSetting::count(),
        ];
    }

    #[Computed]
    public function roleLimits(): \Illuminate\Database\Eloquent\Collection
    {
        return RoleLimit::with('role')->get();
    }

    #[Computed]
    public function rechargeSettings(): \Illuminate\Database\Eloquent\Collection
    {
        return RechargeSetting::with('role')->get();
    }
}; ?>

<div class="space-y-6">
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">System Dashboard</flux:heading>
        <flux:text>Manage roles, limits, and recharge configuration</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Total Roles</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $this->stats['totalRoles'] }}</p>
                </div>
                <flux:icon icon="key" class="h-8 w-8 text-blue-500" />
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Roles with Limits</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $this->stats['rolesWithLimits'] }}</p>
                </div>
                <flux:icon icon="adjustments-horizontal" class="h-8 w-8 text-green-500" />
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Recharge Configs</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $this->stats['rolesWithRecharge'] }}</p>
                </div>
                <flux:icon icon="clock" class="h-8 w-8 text-purple-500" />
            </div>
        </div>
    </div>

    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
        <flux:heading size="lg">Quick Links</flux:heading>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <flux:button href="{{ route('superadmin.roles') }}" variant="ghost" class="justify-start">
                <flux:icon icon="key" />
                Manage Roles
            </flux:button>
            <flux:button href="{{ route('superadmin.role-limits') }}" variant="ghost" class="justify-start">
                <flux:icon icon="adjustments-horizontal" />
                Role Limits
            </flux:button>
            <flux:button href="{{ route('superadmin.recharge-settings') }}" variant="ghost" class="justify-start">
                <flux:icon icon="clock" />
                Recharge Settings
            </flux:button>
        </div>
    </div>
</div>
