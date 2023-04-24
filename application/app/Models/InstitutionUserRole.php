<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Database\Factories\InstitutionUserRoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\InstitutionUserRole
 *
 * @property string $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string $institution_user_id
 * @property string $role_id
 * @property-read InstitutionUser $institutionUser
 * @property-read Role $role
 *
 * @method static InstitutionUserRoleFactory factory($count = null, $state = [])
 * @method static Builder|InstitutionUserRole newModelQuery()
 * @method static Builder|InstitutionUserRole newQuery()
 * @method static Builder|InstitutionUserRole onlyTrashed()
 * @method static Builder|InstitutionUserRole query()
 * @method static Builder|InstitutionUserRole whereCreatedAt($value)
 * @method static Builder|InstitutionUserRole whereDeletedAt($value)
 * @method static Builder|InstitutionUserRole whereId($value)
 * @method static Builder|InstitutionUserRole whereInstitutionUserId($value)
 * @method static Builder|InstitutionUserRole whereRoleId($value)
 * @method static Builder|InstitutionUserRole whereUpdatedAt($value)
 * @method static Builder|InstitutionUserRole withTrashed()
 * @method static Builder|InstitutionUserRole withoutTrashed()
 *
 * @mixin Eloquent
 */
class InstitutionUserRole extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = ['institution_user_id', 'role_id'];

    public function institutionUser(): BelongsTo
    {
        return $this->belongsTo(InstitutionUser::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
