<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Machine;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $dateFrom = '';
    public $dateTo = '';
    public $filterMachine = '';

    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[Computed]
    public function transactions()
    {
        $query = Transaction::with(['user', 'machine', 'slot'])
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo);

        if ($this->filterMachine) {
            $query->where('machine_id', $this->filterMachine);
        }

        return $query->orderByDesc('created_at')->get();
    }

    #[Computed]
    public function machines()
    {
        return Machine::all();
    }

    #[Computed]
    public function stats()
    {
        $query = Transaction::whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo);

        if ($this->filterMachine) {
            $query->where('machine_id', $this->filterMachine);
        }

        return [
            'totalTransactions' => $query->count(),
            'totalRevenue' => $query->sum('price'),
            'avgTransaction' => $query->count() > 0 ? round($query->sum('price') / $query->count()) : 0,
            'successfulTransactions' => $query->where('status', 'completed')->count(),
        ];
    }

    #[Computed]
    public function topProducts()
    {
        $query = Transaction::with('slot')
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo)
            ->where('status', 'completed');

        if ($this->filterMachine) {
            $query->where('machine_id', $this->filterMachine);
        }

        return $query->get()
            ->groupBy(function ($transaction) {
                return $transaction->slot?->category ?? 'Unknown';
            })
            ->map(function ($group) {
                return [
                    'category' => $group->first()->slot?->category ?? 'Unknown',
                    'count' => $group->count(),
                    'revenue' => $group->sum('price'),
                ];
            })
            ->sortByDesc('revenue')
            ->take(5)
            ->values();
    }

    #[Computed]
    public function topEmployees()
    {
        $query = Transaction::with('user')
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo)
            ->where('status', 'completed');

        if ($this->filterMachine) {
            $query->where('machine_id', $this->filterMachine);
        }

        return $query->get()
            ->groupBy('user_id')
            ->map(function ($group) {
                return [
                    'name' => $group->first()->user->name,
                    'email' => $group->first()->user->email,
                    'count' => $group->count(),
                    'spent' => $group->sum('price'),
                ];
            })
            ->sortByDesc('spent')
            ->take(10)
            ->values();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="border-b border-zinc-200 pb-6 dark:border-zinc-700">
        <flux:heading size="xl">Reports & Analytics</flux:heading>
        <p class="text-zinc-600 dark:text-zinc-400">Transaction reports and insights</p>
    </div>

    <!-- Filters -->
    <div class="grid gap-4 md:grid-cols-3">
        <flux:field>
            <flux:label>From Date</flux:label>
            <flux:input type="date" wire:model.live="dateFrom" />
        </flux:field>
        <flux:field>
            <flux:label>To Date</flux:label>
            <flux:input type="date" wire:model.live="dateTo" />
        </flux:field>
        <flux:field>
            <flux:label>Machine</flux:label>
            <flux:select wire:model.live="filterMachine">
                <option value="">All Machines</option>
                @foreach($this->machines as $machine)
                    <option value="{{ $machine->id }}">{{ $machine->code }} - {{ $machine->location }}</option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Transactions</p>
            <p class="mt-2 text-3xl font-semibold">{{ $this->stats['totalTransactions'] }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Revenue</p>
            <p class="mt-2 text-3xl font-semibold">${{ number_format($this->stats['totalRevenue'] / 100, 2) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Avg Transaction</p>
            <p class="mt-2 text-3xl font-semibold">${{ number_format($this->stats['avgTransaction'] / 100, 2) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Success Rate</p>
            <p class="mt-2 text-3xl font-semibold">
                {{ $this->stats['totalTransactions'] > 0 ? round(($this->stats['successfulTransactions'] / $this->stats['totalTransactions']) * 100) : 0 }}%
            </p>
        </div>
    </div>

    <!-- Top Products -->
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Top Products</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Category</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">Purchases</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->topProducts as $product)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="px-6 py-4 text-sm">{{ $product['category'] }}</td>
                            <td class="px-6 py-4 text-right text-sm">{{ $product['count'] }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold">${{ number_format($product['revenue'] / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-zinc-500">No data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Employees -->
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Top Employees</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Employee</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">Purchases</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">Amount Spent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->topEmployees as $employee)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="px-6 py-4 text-sm">
                                <div>
                                    <p class="font-medium">{{ $employee['name'] }}</p>
                                    <p class="text-xs text-zinc-500">{{ $employee['email'] }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">{{ $employee['count'] }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold">${{ number_format($employee['spent'] / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-zinc-500">No data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Recent Transactions</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Machine</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Item</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold">Amount</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->transactions as $transaction)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="px-6 py-4 text-sm">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $transaction->user->name }}</td>
                            <td class="px-6 py-4 text-sm font-mono">{{ $transaction->machine->code }}</td>
                            <td class="px-6 py-4 text-sm">{{ $transaction->slot?->category ?? 'Unknown' }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold">${{ number_format($transaction->price / 100, 2) }}</td>
                            <td class="px-6 py-4 text-center text-sm">
                                @if($transaction->status === 'completed')
                                    <flux:badge variant="success">Completed</flux:badge>
                                @elseif($transaction->status === 'failed')
                                    <flux:badge variant="danger">Failed</flux:badge>
                                @else
                                    <flux:badge variant="warning">{{ ucfirst($transaction->status) }}</flux:badge>
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
