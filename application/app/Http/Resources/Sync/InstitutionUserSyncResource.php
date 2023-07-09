<?php

namespace App\Http\Resources\Sync;

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
            ),
            'user' => new UserSyncResource($this->user),
            'institution' => new InstitutionSyncResource($this->institution),
            'department' => new DepartmentSyncResource($this->department),
            'roles' => RoleSyncResource::collection($this->roles),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
