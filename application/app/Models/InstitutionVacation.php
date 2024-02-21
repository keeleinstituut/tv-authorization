<?php

namespace App\Models;

use App\Util\DateUtil;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * App\Models\InstitutionVacation
 *
 * @property string $id
 * @property string $institution_id
 * @property string $start_date
 * @property string $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static Builder|InstitutionVacation newModelQuery()
 * @method static Builder|InstitutionVacation newQuery()
 * @method static Builder|InstitutionVacation query()
 * @method static Builder|InstitutionVacation whereCreatedAt($value)
 * @method static Builder|InstitutionVacation whereDeletedAt($value)
 * @method static Builder|InstitutionVacation whereEndDate($value)
 * @method static Builder|InstitutionVacation whereId($value)
 * @method static Builder|InstitutionVacation whereInstitutionId($value)
 * @method static Builder|InstitutionVacation whereStartDate($value)
 * @method static Builder|InstitutionVacation whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class InstitutionVacation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'start_date',
        'end_date',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function institutionVacationExclusions(): HasMany
    {
        return $this->hasMany(InstitutionVacationExclusion::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where(
            'end_date', '>',
            Date::now(DateUtil::ESTONIAN_TIMEZONE)->format('Y-m-d')
        );
    }
}
