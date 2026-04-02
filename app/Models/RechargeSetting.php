<?php

namespace App\Models;

use App\Enums\RechargeMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Models\Role;

#[Fillable(['role_id', 'mode', 'recharge_time', 'breakfast_time', 'lunch_time'])]
class RechargeSetting extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'mode' => RechargeMode::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['mode', 'recharge_time', 'breakfast_time', 'lunch_time'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Get the role this setting belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
