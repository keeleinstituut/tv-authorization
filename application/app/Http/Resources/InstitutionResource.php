<?php

namespace App\Http\Resources;

use App\Models\Institution;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Institution
 */
class InstitutionResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     phone: string|null,
     *     email: string|null,
     *     short_name: string|null,
     *     logo_url: string|null,
     *     updated_at: Carbon,
     *     created_at: Carbon,
     *     worktime_timezone: string|null,
     *     monday_worktime_start: string|null,
     *     monday_worktime_end: string|null,
     *     tuesday_worktime_start: string|null,
     *     tuesday_worktime_end: string|null,
     *     wednesday_worktime_start: string|null,
     *     wednesday_worktime_end: string|null,
     *     thursday_worktime_start: string|null,
     *     thursday_worktime_end: string|null,
     *     friday_worktime_start: string|null,
     *     friday_worktime_end: string|null,
     *     saturday_worktime_start: string|null,
     *     saturday_worktime_end: string|null,
     *     sunday_worktime_start: string|null,
     *     sunday_worktime_end: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->only(
            'id',
            'name',
            'short_name',
            'phone',
            'email',
            'logo_url',
            'created_at',
            'updated_at',
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
            'sunday_worktime_end'
        );
    }
}
