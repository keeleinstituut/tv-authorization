<?php

namespace App\Models;

use App\Enums\InstitutionUserStatus;
use App\Models\Scopes\ExcludeArchivedInstitutionUsersScope;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Models\Scopes\ExcludeIfRelatedUserSoftDeletedScope;
use App\Util\DateUtil;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\InstitutionUserFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\InstitutionUser
 *
 * @property string $id
 * @property Carbon|null $created_at
 * @property Carbon|null $deactivation_date
 * @property Carbon|null $archived_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string $institution_id
 * @property string $department_id
 * @property string $user_id
 * @property string $email
 * @property string $phone
 * @property-read Institution $institution
 * @property-read Department $department
 * @property-read User $user
 * @property-read Collection<int, InstitutionUserRole> $institutionUserRoles
 * @property-read int|null $institution_user_roles_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 *
 * @method static InstitutionUserFactory factory($count = null, $state = [])
 * @method static Builder|InstitutionUser newModelQuery()
 * @method static Builder|InstitutionUser newQuery()
 * @method static Builder|InstitutionUser onlyTrashed()
 * @method static Builder|InstitutionUser query()
 * @method static Builder|InstitutionUser whereId($value)
 * @method static Builder|InstitutionUser whereInstitutionId($value)
 * @method static Builder|InstitutionUser whereUserId($value)
 * @method static Builder|InstitutionUser whereEmail($value)
 * @method static Builder|InstitutionUser wherePhone($value)
 * @method static Builder|InstitutionUser whereCreatedAt($value)
 * @method static Builder|InstitutionUser whereUpdatedAt($value)
 * @method static Builder|InstitutionUser whereDeletedAt($value)
 * @method static Builder|InstitutionUser withTrashed()
 * @method static Builder|InstitutionUser withoutTrashed()
 *
 * @mixin Eloquent
 */
class InstitutionUser extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = ['institution_id', 'department_id', 'user_id', 'email', 'phone'];

    protected $casts = ['archived_at' => 'datetime'];

    protected static function booted(): void
    {
        static::addGlobalScope(new ExcludeArchivedInstitutionUsersScope);
        static::addGlobalScope(new ExcludeDeactivatedInstitutionUsersScope);
        static::addGlobalScope(new ExcludeIfRelatedUserSoftDeletedScope);
    }

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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, InstitutionUserRole::class)
            ->wherePivot('deleted_at', null)
            ->withTimestamps();
    }

    public function scopeStatus(Builder $query, InstitutionUserStatus $status): void
    {
        match ($status) {
            InstitutionUserStatus::Active => $query
                ->whereNull('archived_at')
                ->where(
                    fn ($groupedClause) => $groupedClause
                        ->whereNull('deactivation_date')
                        ->orWhereDate('deactivation_date', '>', DateUtil::estonianNow())
                ),
            InstitutionUserStatus::Archived => $query
                ->withoutGlobalScope(ExcludeArchivedInstitutionUsersScope::class)
                ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
                ->whereNotNull('archived_at'),
            InstitutionUserStatus::Deactivated => $query
                ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
                ->whereNull('archived_at')
                ->whereNotNull('deactivation_date')
                ->whereDate('deactivation_date', '<=', DateUtil::currentEstonianDateAtMidnight())
        };
    }

    public function getStatus(): InstitutionUserStatus
    {
        if (filled($this->archived_at)) {
            return InstitutionUserStatus::Archived;
        }

        if ($this->isDeactivated()) {
            return InstitutionUserStatus::Deactivated;
        }

        return InstitutionUserStatus::Active;
    }

    public function isDeactivated(): bool
    {
        return filled($this->deactivation_date)
            && ! $this->deactivation_date->isAfter(DateUtil::currentEstonianDateAtMidnight());
    }

    /** @noinspection PhpUnused */
    protected function deactivationDate(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?CarbonImmutable {
                if (empty($value)) {
                    return null;
                }

                return DateUtil::convertStringToEstonianMidnight($value);
            },
            set: function (CarbonInterface|string|null $value): ?string {
                if ($value instanceof CarbonInterface) {
                    return DateUtil::convertDateTimeObjectToEstonianMidnight($value)->format('Y-m-d');
                }

                return $value;
            }
        );
    }

    public function getDeactivationDateAsString(): ?string
    {
        return $this->deactivation_date?->format('Y-m-d');
    }
}
