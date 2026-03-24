<?php

namespace App\Http\Resources\Sync;

use App\Http\Resources\InstitutionUserVacationsResource;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InstitutionUser
 */
class InstitutionUserSyncResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(
                'id',
                'email',
                'phone',
                'archived_at',
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
            'user' => new UserSyncResource($this->user),
            'institution' => new InstitutionSyncResource($this->institution),
            'department' => new DepartmentSyncResource($this->department),
            'roles' => RoleSyncResource::collection($this->roles),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'status' => $this->getStatus(),
            'vacations' => InstitutionUserVacationsResource::make($this),
        ];
    }
}
