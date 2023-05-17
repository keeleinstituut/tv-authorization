<?php

namespace App\Http\Resources;

use App\Enums\InstitutionUserStatus;
use App\Http\Resources\API\RoleResource;
use App\Models\InstitutionUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin InstitutionUser
 */
class InstitutionUserResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     user: UserResource,
     *     institution: InstitutionResource,
     *     department: DepartmentResource,
     *     roles: ResourceCollection<RoleResource>,
     *     phone: string,
     *     email: string,
     *     status: InstitutionUserStatus,
     *     updated_at: Carbon,
     *     created_at: Carbon
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
            ),
            'user' => new UserResource($this->user),
            'institution' => new InstitutionResource($this->institution),
            'department' => new DepartmentResource($this->department),
            'roles' => RoleResource::collection($this->roles),
        ];
    }
}
