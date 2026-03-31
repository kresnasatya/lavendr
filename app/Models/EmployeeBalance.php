<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'current_balance', 'daily_quota', 'last_recharged_at'])]
class EmployeeBalance extends Model
{
    protected function casts(): array
    {
        return [
            'last_recharged_at' => 'datetime',
        ];
    }

    /**
     * Get the user for this balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
