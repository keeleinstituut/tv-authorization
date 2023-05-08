<?php

namespace App\Models;

use Database\Factories\PrivilegeRoleFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\PrivilegeRole
 *
 * @property string $id
 * @property string $privilege_id
 * @property string $role_id
 * @property-read Privilege $privilege
 * @property-read Role $role
 *
 * @method static PrivilegeRoleFactory factory($count = null, $state = [])
 * @method static Builder|PrivilegeRole newModelQuery()
 * @method static Builder|PrivilegeRole newQuery()
 * @method static Builder|PrivilegeRole query()
 * @method static Builder|PrivilegeRole whereId($value)
 * @method static Builder|PrivilegeRole wherePrivilegeId($value)
 * @method static Builder|PrivilegeRole whereRoleId($value)
 *
 * @mixin Eloquent
 */
class PrivilegeRole extends Pivot
{
    use HasFactory, HasUuids;

    protected $table = 'privilege_roles';

    public $timestamps = false;

    protected $touches = [
        'role',
    ];

    protected $fillable = [
        'role_id',
        'privilege_id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function privilege(): BelongsTo
    {
        return $this->belongsTo(Privilege::class);
    }
}
