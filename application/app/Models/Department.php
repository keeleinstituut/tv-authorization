<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
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
 * App\Models\Department
 *
 * @property string $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string $institution_id
 * @property string $name
 * @property-read Institution $institution
 * @property-read Collection<int, InstitutionUser> $institutionUsers
 * @property-read int|null $institution_users_count
 *
 * @method static DepartmentFactory factory($count = null, $state = [])
 * @method static Builder|InstitutionUser newModelQuery()
 * @method static Builder|InstitutionUser newQuery()
 * @method static Builder|InstitutionUser onlyTrashed()
 * @method static Builder|InstitutionUser query()
 * @method static Builder|InstitutionUser whereId($value)
 * @method static Builder|InstitutionUser whereName($value)
 * @method static Builder|InstitutionUser whereInstitutionId($value)
 * @method static Builder|InstitutionUser whereCreatedAt($value)
 * @method static Builder|InstitutionUser whereDeletedAt($value)
 * @method static Builder|InstitutionUser whereUpdatedAt($value)
 * @method static Builder|InstitutionUser withTrashed()
 * @method static Builder|InstitutionUser withoutTrashed()
 *
 * @mixin Eloquent
 */
class Department extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }
}
