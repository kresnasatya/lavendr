<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'machine_id', 'machine_slot_id', 'points_deducted', 'status', 'notes'])]
class Transaction extends Model
{
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
