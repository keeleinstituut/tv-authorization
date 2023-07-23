<?php

namespace App\Models;

use Database\Factories\InstitutionUserRoleFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\InstitutionUserRole
 *
 * @property string $id
 * @property string $institution_user_id
 * @property string $role_id
 * @property-read InstitutionUser $institutionUser
 * @property-read Role $role
 * @method static InstitutionUserRoleFactory factory($count = null, $state = [])
 * @method static Builder|InstitutionUserRole newModelQuery()
 * @method static Builder|InstitutionUserRole newQuery()
 * @method static Builder|InstitutionUserRole query()
 * @method static Builder|InstitutionUserRole whereId($value)
 * @method static Builder|InstitutionUserRole whereInstitutionUserId($value)
 * @method static Builder|InstitutionUserRole whereRoleId($value)
 * @mixin Eloquent
 */
class InstitutionUserRole extends Pivot
{
    use HasFactory, HasUuids;

    public $timestamps = false;
    protected $table = 'institution_user_roles';

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
