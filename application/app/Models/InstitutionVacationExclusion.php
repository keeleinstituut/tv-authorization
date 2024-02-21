<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\InstitutionVacationExclusion
 *
 * @property string $id
 * @property string $institution_user_id
 * @property string $institution_vacation_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InstitutionUser $institutionUser
 * @property-read InstitutionVacation $institutionVacation
 * @property-read InstitutionVacation $activeInstitutionVacation
 *
 * @method static Builder|InstitutionVacationExclusion newModelQuery()
 * @method static Builder|InstitutionVacationExclusion newQuery()
 * @method static Builder|InstitutionVacationExclusion query()
 * @method static Builder|InstitutionVacationExclusion whereCreatedAt($value)
 * @method static Builder|InstitutionVacationExclusion whereId($value)
 * @method static Builder|InstitutionVacationExclusion whereInstitutionUserId($value)
 * @method static Builder|InstitutionVacationExclusion whereInstitutionVacationId($value)
 * @method static Builder|InstitutionVacationExclusion whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class InstitutionVacationExclusion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'institution_vacation_id',
        'institution_user_id',
    ];

    public function institutionVacation(): BelongsTo
    {
        return $this->belongsTo(InstitutionVacation::class);
    }

    public function activeInstitutionVacation(): BelongsTo
    {
        return $this->institutionVacation()->active();
    }

    public function institutionUser(): BelongsTo
    {
        return $this->belongsTo(InstitutionUser::class);
    }
}
