<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Privilege $privilege
 * @property Role $role
 */
class PrivilegeRole extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function privilege(): BelongsTo
    {
        return $this->belongsTo(Privilege::class);
    }
}
