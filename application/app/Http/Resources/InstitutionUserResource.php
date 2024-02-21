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
    required: [
        'id',
        'user',
        'institution',
        'department',
        'roles',
        'phone',
        'email',
        'status',
        'created_at',
        'updated_at',
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
    ],
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
        new OA\Property(property: 'worktime_timezone', description: 'IANA Timezone Name', type: 'string', example: 'Europe/Tallinn', nullable: true),
        new OA\Property(property: 'monday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'monday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'tuesday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'tuesday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'wednesday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'wednesday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'thursday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'thursday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'friday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'friday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'saturday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'saturday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'sunday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'sunday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
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
     *     archived_at: ?Carbon,
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
        return [
            ...$this->only(
                'id',
                'email',
                'phone',
                'created_at',
                'updated_at',
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
                'sunday_worktime_end'
            ),
            'status' => $this->getStatus(),
            'user' => new UserResource($this->user),
            'institution' => new InstitutionResource($this->institution),
            'department' => new DepartmentResource($this->department),
            'roles' => RoleResource::collection($this->roles),
            'vacations' => new InstitutionUserVacationsResource($this),
        ];
    }
}
