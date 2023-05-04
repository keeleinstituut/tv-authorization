<?php

namespace App\Models;

use Database\Factories\PrivilegeRoleFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\PrivilegeRole
 *
 * @property string $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string $privilege_id
 * @property string $role_id
 * @property-read Privilege $privilege
 * @property-read Role $role
 *
 * @method static PrivilegeRoleFactory factory($count = null, $state = [])
 * @method static Builder|PrivilegeRole newModelQuery()
 * @method static Builder|PrivilegeRole newQuery()
 * @method static Builder|PrivilegeRole onlyTrashed()
 * @method static Builder|PrivilegeRole query()
 * @method static Builder|PrivilegeRole whereCreatedAt($value)
 * @method static Builder|PrivilegeRole whereDeletedAt($value)
 * @method static Builder|PrivilegeRole whereId($value)
 * @method static Builder|PrivilegeRole wherePrivilegeId($value)
 * @method static Builder|PrivilegeRole whereRoleId($value)
 * @method static Builder|PrivilegeRole whereUpdatedAt($value)
 * @method static Builder|PrivilegeRole withTrashed()
 * @method static Builder|PrivilegeRole withoutTrashed()
 *
 * @mixin Eloquent
 */
class PrivilegeRole extends Pivot
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'privilege_roles';

    protected $fillable = ['privilege_id', 'role_id'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function privilege(): BelongsTo
    {
        return $this->belongsTo(Privilege::class);
    }
}
