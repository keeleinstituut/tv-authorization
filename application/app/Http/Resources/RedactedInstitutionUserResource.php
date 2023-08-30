<?php

namespace App\Http\Resources;

use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin InstitutionUser
 */
#[OA\Schema(
    title: 'Institution User Summary',
    required: ['id', 'user', 'institution', 'department', 'phone', 'email'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(
            property: 'user',
            properties: [
                new OA\Property(property: 'forename', type: 'string'),
                new OA\Property(property: 'surname', type: 'string'),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'institution', type: InstitutionResource::class),
        new OA\Property(property: 'department', type: DepartmentResource::class, nullable: true),
        new OA\Property(property: 'phone', type: 'string', format: 'phone'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
    ],
    type: 'object'
)]
class RedactedInstitutionUserResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     user: array{forename: string, surname: string},
     *     institution: InstitutionResource,
     *     department: ?DepartmentResource,
     *     phone: string,
     *     email: string,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(
                'id',
                'email',
                'phone',
            ),
            'user' => [
                'forename' => $this->user->forename,
                'surname' => $this->user->surname,
            ],
            'institution' => new InstitutionResource($this->institution),
            'department' => new DepartmentResource($this->department),
        ];
    }
}
