<?php

namespace App\Models;

use App\Enums\PrivilegeKey;
use Barryvdh\LaravelIdeHelper\Eloquent;
use Database\Factories\PrivilegeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Privilege
 *
 * @property string $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property PrivilegeKey $key
 * @property string|null $description
 * @property-read Collection<int, PrivilegeRole> $privilegeRoles
 * @property-read int|null $privilege_roles_count
 *
 * @method static PrivilegeFactory factory($count = null, $state = [])
 * @method static Builder|Privilege newModelQuery()
 * @method static Builder|Privilege newQuery()
 * @method static Builder|Privilege onlyTrashed()
 * @method static Builder|Privilege query()
 * @method static Builder|Privilege whereCreatedAt($value)
 * @method static Builder|Privilege whereDeletedAt($value)
 * @method static Builder|Privilege whereDescription($value)
 * @method static Builder|Privilege whereId($value)
 * @method static Builder|Privilege whereKey($value)
 * @method static Builder|Privilege whereUpdatedAt($value)
 * @method static Builder|Privilege withTrashed()
 * @method static Builder|Privilege withoutTrashed()
 *
 * @mixin Eloquent
 */
class Privilege extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['key'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'key' => PrivilegeKey::class,
    ];

    public function privilegeRoles(): HasMany
    {
        return $this->hasMany(PrivilegeRole::class);
    }
}
