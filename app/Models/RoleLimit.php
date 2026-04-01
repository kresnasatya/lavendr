<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

#[Fillable(['role_id', 'daily_juice_limit', 'daily_meal_limit', 'daily_snack_limit', 'daily_point_limit'])]
class RoleLimit extends Model
{
    /**
     * Get the role this limit belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
