<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'location', 'is_active'])]
class Machine extends Model
{
    /**
     * Get the slots for this machine.
     */
    public function slots(): HasMany
    {
        return $this->hasMany(MachineSlot::class);
    }

    /**
     * Get the transactions for this machine.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
