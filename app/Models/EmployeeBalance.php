<?php

namespace App\Models;

use Database\Factories\EmployeeBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['user_id', 'current_balance', 'daily_quota', 'last_recharged_at'])]
class EmployeeBalance extends Model
{
    /** @use HasFactory<EmployeeBalanceFactory> */
    use HasFactory;

    use LogsActivity;

    protected function casts(): array
    {
        return [
            'last_recharged_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['current_balance', 'daily_quota'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Get the user for this balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
