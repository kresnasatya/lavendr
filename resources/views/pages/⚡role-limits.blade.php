<?php

use App\Models\RoleLimit;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public bool $showModal = false;
    public ?int $editingRoleId = null;
    public string $editingRoleName = '';
    public int $dailyJuiceLimit = 1;
    public int $dailyMealLimit = 1;
    public int $dailySnackLimit = 1;
    public int $dailyPointLimit = 300;

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function limitsByRole(): \Illuminate\Support\Collection
    {
        return RoleLimit::all()->keyBy('role_id');
    }

    public function openModal(int $roleId, string $roleName): void
    {
        $this->editingRoleId = $roleId;
        $this->editingRoleName = $roleName;

        $limit = $this->limitsByRole[$roleId] ?? null;

        $this->dailyJuiceLimit = $limit?->daily_juice_limit ?? 1;
        $this->dailyMealLimit  = $limit?->daily_meal_limit ?? 1;
        $this->dailySnackLimit = $limit?->daily_snack_limit ?? 1;
        $this->dailyPointLimit = $limit?->daily_point_limit ?? 300;

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingRoleId = null;
        $this->editingRoleName = '';
    }

    public function save(): void
    {
        $this->validate([
            'dailyJuiceLimit' => 'required|integer|min:0',
            'dailyMealLimit'  => 'required|integer|min:0',
            'dailySnackLimit' => 'required|integer|min:0',
            'dailyPointLimit' => 'required|integer|min:0',
        ]);

        RoleLimit::updateOrCreate(
            ['role_id' => $this->editingRoleId],
            [
                'daily_juice_limit' => $this->dailyJuiceLimit,
                'daily_meal_limit'  => $this->dailyMealLimit,
                'daily_snack_limit' => $this->dailySnackLimit,
                'daily_point_limit' => $this->dailyPointLimit,
            ]
        );

        unset($this->limitsByRole);
        $this->closeModal();
    }
}; ?>

<div class="space-y-6">
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">Role Limits</flux:heading>
        <flux:text>Configure daily purchase limits per role</flux:text>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Role</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Juice / day</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Meal / day</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Snack / day</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold">Points / day</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->roles as $role)
                    @php $limit = $this->limitsByRole[$role->id] ?? null; @endphp
                    <tr wire:key="role-{{ $role->id }}" class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm font-medium capitalize text-zinc-900 dark:text-white">
                            {{ $role->name }}
                        </td>
                        @if ($limit)
                            <td class="px-6 py-4 text-center text-sm">{{ $limit->daily_juice_limit }}</td>
                            <td class="px-6 py-4 text-center text-sm">{{ $limit->daily_meal_limit }}</td>
                            <td class="px-6 py-4 text-center text-sm">{{ $limit->daily_snack_limit }}</td>
                            <td class="px-6 py-4 text-center text-sm font-semibold">{{ $limit->daily_point_limit }} pts</td>
                        @else
                            <td colspan="4" class="px-6 py-4 text-center text-sm italic text-zinc-400">No limits set</td>
                        @endif
                        <td class="px-6 py-4 text-right text-sm">
                            <flux:button
                                wire:click="openModal({{ $role->id }}, '{{ $role->name }}')"
                                size="sm"
                                variant="subtle"
                            >
                                {{ $limit ? 'Edit' : 'Set Limits' }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-500">No roles found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal wire:model="showModal" class="max-w-md">
        <flux:heading>Limits for <span class="capitalize">{{ $editingRoleName }}</span></flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Juice per day</flux:label>
                <flux:input type="number" wire:model="dailyJuiceLimit" min="0" />
                <flux:error name="dailyJuiceLimit" />
            </flux:field>

            <flux:field>
                <flux:label>Meal per day</flux:label>
                <flux:input type="number" wire:model="dailyMealLimit" min="0" />
                <flux:error name="dailyMealLimit" />
            </flux:field>

            <flux:field>
                <flux:label>Snack per day</flux:label>
                <flux:input type="number" wire:model="dailySnackLimit" min="0" />
                <flux:error name="dailySnackLimit" />
            </flux:field>

            <flux:field>
                <flux:label>Points per day</flux:label>
                <flux:input type="number" wire:model="dailyPointLimit" min="0" />
                <flux:error name="dailyPointLimit" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">Save</flux:button>
        </div>
    </flux:modal>
</div>
