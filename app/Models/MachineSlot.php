<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['machine_id', 'slot_number', 'category', 'price', 'quantity'])]
class MachineSlot extends Model
{
    /**
     * Get the machine this slot belongs to.
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Get the transactions for this slot.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
