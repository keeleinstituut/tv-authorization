<?php

namespace App\Http\Resources\Sync;

use App\Enums\InstitutionUserStatus;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\UserResource;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin InstitutionUser
 */
class InstitutionUserSyncResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     user: UserResource,
     *     institution_id: string,
     *     department: DepartmentResource,
     *     phone: string,
     *     email: string,
     *     status: InstitutionUserStatus,
     *     updated_at: Carbon,
     *     created_at: Carbon,
     *     deleted_at: Carbon
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(
                'id',
                'email',
                'phone',
                'status',
                'created_at',
                'updated_at',
                'deleted_at'
            ),
            'user' => new UserResource($this->user),
            'department' => new DepartmentResource($this->department),
            'institution_id' => $this->institution_id,
        ];
    }
}
