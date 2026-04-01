<?php

namespace App\Models;

use App\Enums\SlotCategory;
use App\Enums\TransactionStatus;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'machine_id', 'machine_slot_id', 'points_deducted', 'status', 'notes'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
        ];
    }

    /**
     * Count today's successful purchases for a user in a given slot category.
     */
    public static function todayCategoryCount(int $userId, SlotCategory $category): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('status', TransactionStatus::Success)
            ->whereToday('created_at')
            ->whereHas('slot', fn (Builder $q) => $q->where('category', $category))
            ->count();
    }

    /**
     * Get the user who made this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the machine for this transaction.
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Get the slot for this transaction.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(MachineSlot::class, 'machine_slot_id');
    }
}
