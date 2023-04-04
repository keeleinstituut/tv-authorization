<?php

namespace App\Models;

use App\Enum\InstitutionUserStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Institution $institution
 * @property InstitutionUserStatus $status
 * @property User $user
 * @property Collection<InstitutionUserRole> $institutionUserRoles
 */
class InstitutionUser extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

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
