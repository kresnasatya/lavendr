<?php

use App\Enums\RechargeMode;
use App\Models\RechargeSetting;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public bool $showModal = false;
    public ?int $editingRoleId = null;
    public string $editingRoleName = '';
    public string $mode = 'daily';
    public string $rechargeTime = '00:00';
    public string $breakfastTime = '07:00';
    public string $lunchTime = '12:00';

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function settingsByRole(): \Illuminate\Support\Collection
    {
        return RechargeSetting::all()->keyBy('role_id');
    }

    public function openModal(int $roleId, string $roleName): void
    {
        $this->editingRoleId = $roleId;
        $this->editingRoleName = $roleName;

        $setting = $this->settingsByRole[$roleId] ?? null;

        $this->mode          = $setting?->mode->value ?? RechargeMode::Daily->value;
        $this->rechargeTime  = $setting?->recharge_time ? substr($setting->recharge_time, 0, 5) : '00:00';
        $this->breakfastTime = $setting?->breakfast_time ? substr($setting->breakfast_time, 0, 5) : '07:00';
        $this->lunchTime     = $setting?->lunch_time ? substr($setting->lunch_time, 0, 5) : '12:00';

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
        $rules = [
            'mode'        => 'required|in:daily,dual_period',
            'rechargeTime' => 'required|date_format:H:i',
        ];

        if ($this->mode === RechargeMode::DualPeriod->value) {
            $rules['breakfastTime'] = 'required|date_format:H:i';
            $rules['lunchTime']     = 'required|date_format:H:i';
        }

        $this->validate($rules);

        RechargeSetting::updateOrCreate(
            ['role_id' => $this->editingRoleId],
            [
                'mode'           => $this->mode,
                'recharge_time'  => $this->rechargeTime.':00',
                'breakfast_time' => $this->mode === RechargeMode::DualPeriod->value ? $this->breakfastTime.':00' : null,
                'lunch_time'     => $this->mode === RechargeMode::DualPeriod->value ? $this->lunchTime.':00' : null,
            ]
        );

        unset($this->settingsByRole);
        $this->closeModal();
    }
}; ?>

<div class="space-y-6">
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">Recharge Settings</flux:heading>
        <flux:text>Configure when employee balances are automatically recharged per role</flux:text>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold">Role</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Mode</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Recharge Time</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Breakfast Time</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Lunch Time</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->roles as $role)
                    @php $setting = $this->settingsByRole[$role->id] ?? null; @endphp
                    <tr wire:key="role-{{ $role->id }}" class="border-b border-zinc-200 dark:border-zinc-700">
                        <td class="px-6 py-4 text-sm font-medium capitalize text-zinc-900 dark:text-white">
                            {{ $role->name }}
                        </td>
                        @if ($setting)
                            <td class="px-6 py-4 text-sm">
                                <flux:badge>{{ ucfirst(str_replace('_', ' ', $setting->mode->value)) }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm">{{ substr($setting->recharge_time, 0, 5) }}</td>
                            <td class="px-6 py-4 font-mono text-sm">{{ $setting->breakfast_time ? substr($setting->breakfast_time, 0, 5) : '—' }}</td>
                            <td class="px-6 py-4 font-mono text-sm">{{ $setting->lunch_time ? substr($setting->lunch_time, 0, 5) : '—' }}</td>
                        @else
                            <td colspan="4" class="px-6 py-4 text-center text-sm italic text-zinc-400">No config set</td>
                        @endif
                        <td class="px-6 py-4 text-right text-sm">
                            <flux:button
                                wire:click="openModal({{ $role->id }}, '{{ $role->name }}')"
                                size="sm"
                                variant="subtle"
                            >
                                {{ $setting ? 'Edit' : 'Configure' }}
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
        <flux:heading>Recharge Settings for <span class="capitalize">{{ $editingRoleName }}</span></flux:heading>

        <div class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Mode</flux:label>
                <flux:select wire:model.live="mode">
                    <flux:select.option value="daily">Daily (once per day)</flux:select.option>
                    <flux:select.option value="dual_period">Dual Period (breakfast + lunch)</flux:select.option>
                </flux:select>
                <flux:error name="mode" />
            </flux:field>

            @if ($mode === 'daily')
                <flux:field>
                    <flux:label>Recharge Time</flux:label>
                    <flux:input type="time" wire:model="rechargeTime" />
                    <flux:error name="rechargeTime" />
                </flux:field>
            @else
                <flux:field>
                    <flux:label>Breakfast Recharge Time</flux:label>
                    <flux:input type="time" wire:model="breakfastTime" />
                    <flux:error name="breakfastTime" />
                </flux:field>

                <flux:field>
                    <flux:label>Lunch Recharge Time</flux:label>
                    <flux:input type="time" wire:model="lunchTime" />
                    <flux:error name="lunchTime" />
                </flux:field>
            @endif
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">Save</flux:button>
        </div>
    </flux:modal>
</div>
