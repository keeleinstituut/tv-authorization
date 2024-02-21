<?php

namespace App\Models;

use App\Util\DateUtil;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * App\Models\InstitutionUserVacation
 *
 *
 * @property string $id
 * @property string $institution_user_id
 * @property string $start_date
 * @property string $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @method static Builder|InstitutionUserVacation newModelQuery()
 * @method static Builder|InstitutionUserVacation newQuery()
 * @method static Builder|InstitutionUserVacation query()
 * @mixin Eloquent
 */
class InstitutionUserVacation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'institution_user_id',
        'start_date',
        'end_date'
    ];

    public function institutionUser(): BelongsTo
    {
        return $this->belongsTo(InstitutionUser::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where(
            'end_date', '>',
            Date::now(DateUtil::ESTONIAN_TIMEZONE)->format('Y-m-d')
        );
    }
}
