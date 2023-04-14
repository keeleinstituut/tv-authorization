<?php

namespace App\Models;

use App\Enums\InstitutionUserStatus;
use Database\Factories\InstitutionUserFactory;
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
 * App\Models\InstitutionUser
 *
 * @property string $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string $institution_id
 * @property string $user_id
 * @property string $email
 * @property InstitutionUserStatus $status
 * @property-read Institution $institution
 *
 * @property-read Collection<int, InstitutionUserRole> $institutionUserRoles
 * @property-read int|null $institution_user_roles_count
 * @property-read User $user
 *
 * @method static InstitutionUserFactory factory($count = null, $state = [])
 * @method static Builder|InstitutionUser newModelQuery()
 * @method static Builder|InstitutionUser newQuery()
 * @method static Builder|InstitutionUser onlyTrashed()
 * @method static Builder|InstitutionUser query()
 * @method static Builder|InstitutionUser whereCreatedAt($value)
 * @method static Builder|InstitutionUser whereDeletedAt($value)
 * @method static Builder|InstitutionUser whereId($value)
 * @method static Builder|InstitutionUser whereInstitutionId($value)
 * @method static Builder|InstitutionUser whereStatus($value)
 * @method static Builder|InstitutionUser whereUpdatedAt($value)
 * @method static Builder|InstitutionUser whereUserId($value)
 * @method static Builder|InstitutionUser withTrashed()
 * @method static Builder|InstitutionUser withoutTrashed()
 *
 * @mixin Eloquent
 */
class InstitutionUser extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = ['institution_id', 'user_id', 'status', 'email'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, mixed>
     */
    protected $casts = [
        'status' => InstitutionUserStatus::class,
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institutionUserRoles(): HasMany
    {
        return $this->hasMany(InstitutionUserRole::class);
    }
}
