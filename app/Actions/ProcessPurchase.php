<?php

namespace App\Actions;

use App\Enums\TransactionStatus;
use App\Models\EmployeeBalance;
use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProcessPurchase
{
    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function handle(string $cardNumber, string $machineId, int $slotNumber, int $productPrice): array
    {
        $user = User::where('card_number', $cardNumber)->where('is_active', true)->first();

        if (! $user) {
            return $this->reject('Card not recognised or employee is inactive.');
        }

        if (! $user->can('purchase')) {
            return $this->reject('Employee is not authorised to purchase.');
        }

        $machine = Machine::where('code', $machineId)->where('is_active', true)->first();

        if (! $machine) {
            return $this->reject('Vending machine not found or inactive.');
        }

        $slot = MachineSlot::where('machine_id', $machine->id)
            ->where('slot_number', $slotNumber)
            ->first();

        if (! $slot) {
            return $this->reject('Slot not found on this machine.');
        }

        if ($slot->price !== $productPrice) {
            return $this->reject('Product price mismatch.');
        }

        return DB::transaction(function () use ($user, $machine, $slot) {
            /** @var EmployeeBalance $balance */
            $balance = EmployeeBalance::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $balance || $balance->current_balance < $slot->price) {
                $this->logFailedTransaction($user, $machine, $slot, 'Insufficient balance.');

                return $this->reject('Insufficient balance.');
            }

            $roleLimit = $user->roleLimit();
            $category = $slot->category;

            if ($roleLimit) {
                $limitField = "daily_{$category->value}_limit";
                $dailyLimit = $roleLimit->{$limitField};
                $todayCount = Transaction::todayCategoryCount($user->id, $category);

                if ($todayCount >= $dailyLimit) {
                    $this->logFailedTransaction($user, $machine, $slot, "Daily {$category->value} limit reached.");

                    return $this->reject("Daily {$category->value} limit reached.");
                }
            }

            $balance->decrement('current_balance', $slot->price);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'machine_id' => $machine->id,
                'machine_slot_id' => $slot->id,
                'points_deducted' => $slot->price,
                'status' => TransactionStatus::Success,
            ]);

            return [
                'success' => true,
                'message' => 'Purchase successful.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'points_deducted' => $slot->price,
                    'remaining_balance' => $balance->fresh()->current_balance,
                ],
            ];
        });
    }

    private function logFailedTransaction(User $user, Machine $machine, MachineSlot $slot, string $reason): void
    {
        Transaction::create([
            'user_id' => $user->id,
            'machine_id' => $machine->id,
            'machine_slot_id' => $slot->id,
            'points_deducted' => 0,
            'status' => TransactionStatus::Failed,
            'notes' => $reason,
        ]);
    }

    /**
     * @return array{success: false, message: string}
     */
    private function reject(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
