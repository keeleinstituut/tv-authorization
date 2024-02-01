<?php

namespace App\Models;

use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Models\AuditLoggable;
use Database\Factories\InstitutionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Institution
 *
 * @property string $id
 * @property string|null $short_name
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string|null $logo_url
 * @property string|null worktime_timezone
 * @property string|null monday_worktime_start
 * @property string|null monday_worktime_end
 * @property string|null tuesday_worktime_start
 * @property string|null tuesday_worktime_end
 * @property string|null wednesday_worktime_start
 * @property string|null wednesday_worktime_end
 * @property string|null thursday_worktime_start
 * @property string|null thursday_worktime_end
 * @property string|null friday_worktime_start
 * @property string|null friday_worktime_end
 * @property string|null saturday_worktime_start
 * @property string|null saturday_worktime_end
 * @property string|null sunday_worktime_start
 * @property string|null sunday_worktime_end
 * @property-read Collection<int, InstitutionUser> $institutionUsers
 * @property-read int|null $institution_users_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, Department> $departments
 * @property-read Collection<int, InstitutionVacation> $vacations
 * @property-read int|null $departments_count
 *
 * @method static InstitutionFactory factory($count = null, $state = [])
 * @method static Builder|Institution newModelQuery()
 * @method static Builder|Institution newQuery()
 * @method static Builder|Institution onlyTrashed()
 * @method static Builder|Institution query()
 * @method static Builder|Institution whereCreatedAt($value)
 * @method static Builder|Institution whereDeletedAt($value)
 * @method static Builder|Institution whereId($value)
 * @method static Builder|Institution whereShortName($value)
 * @method static Builder|Institution whereLogoUrl($value)
 * @method static Builder|Institution whereEmail($value)
 * @method static Builder|Institution wherePhone($value)
 * @method static Builder|Institution whereName($value)
 * @method static Builder|Institution whereUpdatedAt($value)
 * @method static Builder|Institution withTrashed()
 * @method static Builder|Institution withoutTrashed()
 * @method static Builder|Institution whereWorktimeTimezone($value)
 * @method static Builder|Institution whereMondayWorktimeStart($value)
 * @method static Builder|Institution whereMondayWorktimeEnd($value)
 * @method static Builder|Institution whereTuesdayWorktimeStart($value)
 * @method static Builder|Institution whereTuesdayWorktimeEnd($value)
 * @method static Builder|Institution whereWednesdayWorktimeStart($value)
 * @method static Builder|Institution whereWednesdayWorktimeEnd($value)
 * @method static Builder|Institution whereThursdayWorktimeStart($value)
 * @method static Builder|Institution whereThursdayWorktimeEnd($value)
 * @method static Builder|Institution whereFridayWorktimeStart($value)
 * @method static Builder|Institution whereFridayWorktimeEnd($value)
 * @method static Builder|Institution whereSaturdayWorktimeStart($value)
 * @method static Builder|Institution whereSaturdayWorktimeEnd($value)
 * @method static Builder|Institution whereSundayWorktimeStart($value)
 * @method static Builder|Institution whereSundayWorktimeEnd($value)
 *
 * @mixin Eloquent
 */
class Institution extends Model implements AuditLoggable
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'name',
        'logo_url',
        'short_name',
        'email',
        'phone',
        'worktime_timezone',
        'monday_worktime_start',
        'monday_worktime_end',
        'tuesday_worktime_start',
        'tuesday_worktime_end',
        'wednesday_worktime_start',
        'wednesday_worktime_end',
        'thursday_worktime_start',
        'thursday_worktime_end',
        'friday_worktime_start',
        'friday_worktime_end',
        'saturday_worktime_start',
        'saturday_worktime_end',
        'sunday_worktime_start',
        'sunday_worktime_end',
    ];

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(InstitutionVacation::class);
    }

    public static function queryByUserPersonalIdentificationCode(string $personalIdentificationCode): Builder
    {
        return static::whereHas(
            'institutionUsers',
            fn (Builder $institutionUserQuery) => $institutionUserQuery->whereHas(
                'user',
                fn (Builder $userQuery) => $userQuery->where(
                    'personal_identification_code',
                    $personalIdentificationCode
                )
            )
        );
    }

    public function getIdentitySubset(): array
    {
        return $this->only(['id', 'name']);
    }

    public function getAuditLogRepresentation(): array
    {
        return $this->withoutRelations()->refresh()->toArray();
    }

    public function getAuditLogObjectType(): AuditLogEventObjectType
    {
        return AuditLogEventObjectType::Institution;
    }
}
