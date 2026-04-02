<?php

use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

new class extends Component
{
    public string $searchTerm = '';
    public ?string $filterAction = null;
    public ?string $filterDate = null;

    #[Computed]
    public function activities(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Activity::with('causer', 'subject')
            ->latest();

        if ($this->filterAction) {
            $query->where('description', 'like', '%'.$this->filterAction.'%');
        }

        if ($this->filterDate) {
            $query->whereDate('created_at', $this->filterDate);
        }

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%'.$this->searchTerm.'%')
                    ->orWhereHas('causer', fn ($q) => $q->where('name', 'like', '%'.$this->searchTerm.'%'));
            });
        }

        return $query->paginate(50);
    }

    public function clearFilters(): void
    {
        $this->searchTerm = '';
        $this->filterAction = null;
        $this->filterDate = null;
        unset($this->activities);
    }
}; ?>

<div class="space-y-6">
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">Audit Logs</flux:heading>
        <flux:text>Track all system changes and actions</flux:text>
    </div>

    {{-- Filters --}}
    <div class="grid gap-4 md:grid-cols-4">
        <flux:field>
            <flux:label>Search</flux:label>
            <flux:input wire:model.live="searchTerm" type="text" placeholder="Action or user…" />
        </flux:field>

        <flux:field>
            <flux:label>Action</flux:label>
            <flux:select wire:model.live="filterAction">
                <flux:select.option value="">All Actions</flux:select.option>
                <flux:select.option value="updated">Updated</flux:select.option>
                <flux:select.option value="created">Created</flux:select.option>
                <flux:select.option value="deleted">Deleted</flux:select.option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>Date</flux:label>
            <flux:input type="date" wire:model.live="filterDate" />
        </flux:field>

        <div class="flex items-end">
            <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">
                Clear Filters
            </flux:button>
        </div>
    </div>

    {{-- Activity List --}}
    <div class="space-y-3">
        @forelse ($this->activities as $activity)
            <div wire:key="activity-{{ $activity->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <flux:text size="sm" class="text-zinc-500">{{ $activity->created_at->format('M d, Y H:i') }}</flux:text>
                            @if ($activity->causer)
                                <flux:badge size="sm">{{ $activity->causer->name }}</flux:badge>
                            @endif
                        </div>

                        <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $activity->description }}
                        </p>

                        @if ($activity->subject_type)
                            <flux:text size="sm" class="mt-1 text-zinc-500">
                                {{ str_replace('App\\Models\\', '', $activity->subject_type) }} #{{ $activity->subject_id }}
                            </flux:text>
                        @endif

                        @if ($activity->changes?->attributes ?? [])
                            <div class="mt-3 rounded-md bg-zinc-50 p-3 dark:bg-zinc-800">
                                <flux:text size="sm" class="font-semibold">Changes:</flux:text>
                                <dl class="mt-2 space-y-1 text-sm">
                                    @foreach ($activity->changes->attributes as $field => $change)
                                        <div class="flex gap-4">
                                            <dt class="font-medium text-zinc-700 dark:text-zinc-300 w-32">{{ ucfirst(str_replace('_', ' ', $field)) }}</dt>
                                            <dd class="flex flex-1 gap-4">
                                                @if (is_array($change['old'] ?? null))
                                                    <span class="rounded bg-red-100 px-2 py-0.5 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                        {{ $change['old']['value'] ?? $change['old']['name'] ?? 'null' }}
                                                    </span>
                                                @else
                                                    <span class="rounded bg-red-100 px-2 py-0.5 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                        {{ $change['old'] ?? 'null' }}
                                                    </span>
                                                @endif
                                                @if (is_array($change['new'] ?? null))
                                                    <span class="rounded bg-green-100 px-2 py-0.5 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                        {{ $change['new']['value'] ?? $change['new']['name'] ?? 'null' }}
                                                    </span>
                                                @else
                                                    <span class="rounded bg-green-100 px-2 py-0.5 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                        {{ $change['new'] ?? 'null' }}
                                                    </span>
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endif

                        @if ($activity->ip_address || $activity->user_agent)
                            <flux:text size="xs" class="mt-2 text-zinc-400">
                                IP: {{ $activity->ip_address }} • {{ Str::limit($activity->user_agent, 50) }}
                            </flux:text>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-600">
                <flux:text>No audit logs found</flux:text>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($this->activities->hasPages())
        <div class="flex justify-center">
            {{ $this->activities->links() }}
        </div>
    @endif
</div>
