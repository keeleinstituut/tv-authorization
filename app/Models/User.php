<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * App\Models\User
 *
 * @property string $id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $forename
 * @property string $surname
 * @property string $personal_identification_code
 * @property-read Collection<int, InstitutionUser> $institutionUsers
 * @property-read int|null $institution_users_count
 *
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User onlyTrashed()
 * @method static Builder|User query()
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereDeletedAt($value)
 * @method static Builder|User whereForename($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User wherePersonalIdentificationCode($value)
 * @method static Builder|User whereSurname($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User withTrashed()
 * @method static Builder|User withoutTrashed()
 *
 * @mixin Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, SoftDeletes, HasUuids;

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }
}
