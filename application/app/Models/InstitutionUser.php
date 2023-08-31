<?php

namespace App\Models;

use App\Enums\InstitutionUserStatus;
use App\Enums\PrivilegeKey;
use App\Models\Scopes\ExcludeArchivedInstitutionUsersScope;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Models\Scopes\ExcludeIfRelatedUserSoftDeletedScope;
use App\Util\DateUtil;
use Carbon\CarbonImmutable;
use Database\Factories\InstitutionUserFactory;
use DateTimeInterface;
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
use Illuminate\Support\Facades\Date;

/**
 * App\Models\InstitutionUser
 *
 * @property string $id
 * @property Carbon|null $created_at
 * @property string|null $deactivation_date
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
 * @method static Builder|InstitutionUser status(InstitutionUserStatus $value)
 * @method static Builder|InstitutionUser statusIn(array $statuses)
 * @method static Builder|InstitutionUser hasPrivileges(PrivilegeKey|string ...$privileges)
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
        return $this->belongsToMany(Role::class, InstitutionUserRole::class)->withTimestamps();
    }

    /**
     * @noinspection PhpUnused
     *
     * @param  array<InstitutionUserStatus|string>  $statuses
     */
    public function scopeStatusIn(Builder $query, array $statuses): void
    {
        $query
            ->withoutGlobalScope(ExcludeArchivedInstitutionUsersScope::class)
            ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
            ->where(
                fn (Builder $clause) => collect($statuses)->each($clause->orWhere->status(...))
            );
    }

    /** @noinspection PhpUnused */
    public function scopeStatus(Builder $query, InstitutionUserStatus|string $status): void
    {
        if (is_string($status)) {
            $status = InstitutionUserStatus::from($status);
            /** @var InstitutionUserStatus $status */
        }

        match ($status) {
            InstitutionUserStatus::Active => $query
                ->whereNull('archived_at')
                ->where(
                    fn ($groupedClause) => $groupedClause
                        ->whereNull('deactivation_date')
                        ->orWhereDate('deactivation_date', '>', Date::now(DateUtil::ESTONIAN_TIMEZONE)->format('Y-m-d'))
                ),
            InstitutionUserStatus::Archived => $query
                ->withoutGlobalScope(ExcludeArchivedInstitutionUsersScope::class)
                ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
                ->whereNotNull('archived_at'),
            InstitutionUserStatus::Deactivated => $query
                ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
                ->whereNull('archived_at')
                ->whereNotNull('deactivation_date')
                ->whereDate('deactivation_date', '<=', Date::now(DateUtil::ESTONIAN_TIMEZONE)->format('Y-m-d'))
        };
    }

    public function getStatus(): InstitutionUserStatus
    {
        if ($this->isArchived()) {
            return InstitutionUserStatus::Archived;
        }

        if ($this->isDeactivated()) {
            return InstitutionUserStatus::Deactivated;
        }

        return InstitutionUserStatus::Active;
    }

    public function isArchived(): bool
    {
        return filled($this->archived_at);
    }

    public function isDeactivated(): bool
    {
        return filled($this->deactivation_date)
            && ! Date::parse($this->deactivation_date, DateUtil::ESTONIAN_TIMEZONE)->isFuture();
    }

    /** @noinspection PhpUnused */
    protected function deactivationDate(): Attribute
    {
        return Attribute::make(
            set: function (DateTimeInterface|string|null $value): ?string {
                if ($value instanceof DateTimeInterface) {
                    return CarbonImmutable::parse($value)
                        ->timezone(DateUtil::ESTONIAN_TIMEZONE)
                        ->format('Y-m-d');
                }

                return $value;
            }
        );
    }

    public function isOnlyUserWithRootRole(): bool
    {
        return $this->roles()
            ->where('is_root', true)
            ->has('institutionUsers', '=', 1)
            ->exists();
    }

    public function hasRootRole(): bool
    {
        return $this->roles()
            ->where('is_root', true)
            ->exists();
    }

    /**
     * @noinspection PhpUnused
     */
    public function scopeHasPrivileges(Builder $query, PrivilegeKey|string ...$privileges): void
    {
        collect($privileges)
            ->map(fn (PrivilegeKey|string $privilege) => is_string($privilege)
                ? InstitutionUserStatus::from($privilege)
                : $privilege
            )
            ->each(function (PrivilegeKey $privilege) use ($query) {
                $query->whereHas('roles', function (Builder $roleQuery) use ($privilege) {
                    $roleQuery
                        ->whereNull('deleted_at')
                        ->whereHas('privileges', function (Builder $privilegeQuery) use ($privilege) {
                            $privilegeQuery->whereNull('deleted_at')->where('key', $privilege->value);
                        });
                });
            });
    }

    /**
     * @return \Illuminate\Support\Collection<PrivilegeKey>
     */
    public function collectPrivileges(): \Illuminate\Support\Collection
    {
        return $this->roles
            ->flatMap(fn (Role $role) => $role->privileges)
            ->map(fn (Privilege $privilege) => $privilege->key);
    }

    public function hasPrivileges(PrivilegeKey ...$expectedPrivileges): bool
    {
        return collect($expectedPrivileges)
            ->map(fn (PrivilegeKey $privilege) => $privilege->value)
            ->diff($this->collectPrivileges()->map(fn (PrivilegeKey $privilege) => $privilege->value))
            ->isEmpty();
    }

    public function scopeIsLikeName(Builder $query, string $name): void
    {
        $query->whereHas('user', function (Builder $userQuery) use ($name) {
            $userQuery->whereRaw("forename || ' ' || surname ILIKE ?", ["%$name%"]);
        });
    }
}
