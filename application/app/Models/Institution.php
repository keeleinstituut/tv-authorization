<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Institution
 *
 * @property string $id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string|null $logo_url
 * @property-read Collection<int, InstitutionUser> $institutionUsers
 * @property-read int|null $institution_users_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 *
 * @method static InstitutionFactory factory($count = null, $state = [])
 * @method static Builder|Institution newModelQuery()
 * @method static Builder|Institution newQuery()
 * @method static Builder|Institution onlyTrashed()
 * @method static Builder|Institution query()
 * @method static Builder|Institution whereCreatedAt($value)
 * @method static Builder|Institution whereDeletedAt($value)
 * @method static Builder|Institution whereId($value)
 * @method static Builder|Institution whereName($value)
 * @method static Builder|Institution whereUpdatedAt($value)
 * @method static Builder|Institution withTrashed()
 * @method static Builder|Institution withoutTrashed()
 *
 * @mixin Eloquent
 */
class Institution extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = ['name', 'logo_url'];

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
