<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Role
 *
 * @property string $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string $institution_id
 * @property string $name
 *
 * @property-read Institution $institution
 * @property-read Collection<int, InstitutionUserRole> $institutionUserRoles
 * @property-read int|null $institution_user_roles_count
 * @property-read Collection<int, PrivilegeRole> $privilegeRoles
 * @property-read int|null $privilege_roles_count
 *
 * @method static RoleFactory factory($count = null, $state = [])
 * @method static Builder|Role newModelQuery()
 * @method static Builder|Role newQuery()
 * @method static Builder|Role onlyTrashed()
 * @method static Builder|Role query()
 * @method static Builder|Role whereCreatedAt($value)
 * @method static Builder|Role whereDeletedAt($value)
 * @method static Builder|Role whereId($value)
 * @method static Builder|Role whereInstitutionId($value)
 * @method static Builder|Role whereName($value)
 * @method static Builder|Role whereUpdatedAt($value)
 * @method static Builder|Role withTrashed()
 * @method static Builder|Role withoutTrashed()
 *
 * @mixin Eloquent
 */
class Role extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = ['institution_id', 'name'];

    public function institutionUserRoles(): HasMany
    {
        return $this->hasMany(InstitutionUserRole::class);
    }

    public function privilegeRoles(): HasMany
    {
        return $this->hasMany(PrivilegeRole::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
