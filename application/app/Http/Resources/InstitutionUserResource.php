<?php

namespace App\Http\Resources;

use App\Enums\InstitutionUserStatus;
use App\Http\Resources\API\RoleResource;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

/**
 * @mixin InstitutionUser
 */
#[OA\Schema(
    title: 'Institution User',
    required: ['id', 'user', 'institution', 'department', 'roles', 'phone', 'email', 'status', 'created_at', 'updated_at', 'archived_at', 'deactivation_date'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'user', type: UserResource::class),
        new OA\Property(property: 'institution', type: InstitutionResource::class),
        new OA\Property(property: 'department', type: DepartmentResource::class, nullable: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(ref: RoleResource::class)),
        new OA\Property(property: 'phone', type: 'string', format: 'phone'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'status', type: 'string', enum: InstitutionUserStatus::class),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'archived_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'deactivation_date', type: 'string', format: 'date', nullable: true),
    ],
    type: 'object'
)]
class InstitutionUserResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     user: UserResource,
     *     institution: InstitutionResource,
     *     department: ?DepartmentResource,
     *     roles: ResourceCollection<RoleResource>,
     *     phone: string,
     *     email: string,
     *     status: InstitutionUserStatus,
     *     updated_at: Carbon,
     *     created_at: Carbon,
     *     deactivation_date: ?string,
     *     archived_at: ?Carbon
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(
                'id',
                'email',
                'phone',
                'created_at',
                'updated_at',
                'archived_at',
                'deactivation_date',
            ),
            'status' => $this->getStatus(),
            'user' => new UserResource($this->user),
            'institution' => new InstitutionResource($this->institution),
            'department' => new DepartmentResource($this->department),
            'roles' => RoleResource::collection($this->roles),
        ];
    }
}
