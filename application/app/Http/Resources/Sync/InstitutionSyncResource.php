<?php

namespace App\Http\Resources\Sync;

use App\Models\Institution;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Institution
 */
class InstitutionSyncResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     phone: string,
     *     email: string,
     *     short_name: string,
     *     logo_url: string,
     *     deleted_at: Carbon,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(
                'id',
                'name',
                'short_name',
                'phone',
                'email',
                'logo_url',
                'deleted_at',
                'deactivation_date',
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
            ),
            'vacations' => $this->whenLoaded('vacations'),
        ];
    }
}
