<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $name
 * @property Collection<InstitutionUser> $institutionUsers
 * @property Collection<Role> $roles
 */
class Institution extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
