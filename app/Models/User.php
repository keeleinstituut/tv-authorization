<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $forename
 * @property string $surname
 * @property string $personal_identification_code
 * @property Collection<InstitutionUser> $institutionUsers
 */
class User extends Authenticatable
{
    use HasFactory, SoftDeletes, HasUuids;

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }
}
