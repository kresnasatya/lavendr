<?php

use App\Enums\TransactionStatus;
use App\Models\Machine;
use App\Models\Transaction;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public ?int $filterMachine = null;

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo   = now()->format('Y-m-d');
    }

    #[Computed]
    public function machines(): \Illuminate\Database\Eloquent\Collection
    {
        return Machine::orderBy('code')->get();
    }

    #[Computed]
    public function stats(): array
    {
        $query = Transaction::whereBetween('created_at', [
            $this->dateFrom.' 00:00:00',
            $this->dateTo.' 23:59:59',
        ]);

        if ($this->filterMachine) {
            $query->where('machine_id', $this->filterMachine);
        }

        $total   = (clone $query)->count();
        $success = (clone $query)->where('status', TransactionStatus::Success)->count();
        $points  = (clone $query)->where('status', TransactionStatus::Success)->sum('points_deducted');

        return [
            'total'       => $total,
            'successful'  => $success,
            'failed'      => $total - $success,
            'pointsSpent' => $points,
            'successRate' => $total > 0 ? round($success / $total * 100) : 0,
        ];
    }

    #[Computed]
    public function categoryBreakdown(): \Illuminate\Support\Collection
    {
        return Transaction::with('slot')
            ->where('status', TransactionStatus::Success)
            ->whereBetween('created_at', [
                $this->dateFrom.' 00:00:00',
                $this->dateTo.' 23:59:59',
            ])
            ->when($this->filterMachine, fn ($q) => $q->where('machine_id', $this->filterMachine))
            ->get()
            ->groupBy(fn ($tx) => $tx->slot?->category?->value ?? 'unknown')
            ->map(fn ($group, $category) => [
                'category' => $category,
                'count'    => $group->count(),
                'points'   => $group->sum('points_deducted'),
            ])
            ->sortByDesc('count')
            ->values();
    }

    #[Computed]
    public function transactions(): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::with(['user', 'machine', 'slot'])
            ->whereBetween('created_at', [
                $this->dateFrom.' 00:00:00',
                $this->dateTo.' 23:59:59',
            ])
            ->when($this->filterMachine, fn ($q) => $q->where('machine_id', $this->filterMachine))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">Reports</flux:heading>
        <flux:text>Transaction history and purchase statistics</flux:text>
    </div>

    {{-- Filters --}}
    <div class="grid gap-4 md:grid-cols-3">
        <flux:field>
            <flux:label>From</flux:label>
            <flux:input type="date" wire:model.live="dateFrom" />
        </flux:field>
        <flux:field>
            <flux:label>To</flux:label>
            <flux:input type="date" wire:model.live="dateTo" />
        </flux:field>
        <flux:field>
            <flux:label>Machine</flux:label>
            <flux:select wire:model.live="filterMachine">
                <flux:select.option value="">All Machines</flux:select.option>
                @foreach ($this->machines as $machine)
                    <flux:select.option value="{{ $machine->id }}">{{ $machine->code }} — {{ $machine->location }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    {{-- Summary --}}
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Total Transactions</flux:text>
            <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['total'] }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Successful</flux:text>
            <p class="mt-1 text-3xl font-bold text-green-600">{{ $this->stats['successful'] }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Points Spent</flux:text>
            <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($this->stats['pointsSpent']) }} pts</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Success Rate</flux:text>
            <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['successRate'] }}%</p>
        </div>
    </div>

    {{-- Category Breakdown --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <flux:heading size="lg">By Category</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-6 py-3 text-left text-sm font-semibold">Category</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">Purchases</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">Points Spent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->categoryBreakdown as $row)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="px-6 py-4 text-sm capitalize">{{ $row['category'] }}</td>
                            <td class="px-6 py-4 text-right text-sm">{{ $row['count'] }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold">{{ number_format($row['points']) }} pts</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-zinc-500">No data for this period</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <flux:heading size="lg">Recent Transactions</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-6 py-3 text-left text-sm font-semibold">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Machine</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Category</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">Points</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->transactions as $tx)
                        <tr wire:key="tx-{{ $tx->id }}" class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="px-6 py-4 text-sm text-zinc-500">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $tx->user->name }}</td>
                            <td class="px-6 py-4 font-mono text-sm">{{ $tx->machine->code }}</td>
                            <td class="px-6 py-4 text-sm capitalize">{{ $tx->slot?->category?->value ?? '—' }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold">
                                @if ($tx->status === TransactionStatus::Success)
                                    {{ $tx->points_deducted }} pts
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-sm">
                                @if ($tx->status === TransactionStatus::Success)
                                    <flux:badge variant="success">Success</flux:badge>
                                @elseif ($tx->status === TransactionStatus::Failed)
                                    <flux:badge variant="danger">Failed</flux:badge>
                                @else
                                    <flux:badge variant="warning">{{ ucfirst($tx->status->value) }}</flux:badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-500">No transactions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
