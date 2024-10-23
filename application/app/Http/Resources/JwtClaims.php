<?php

namespace App\Http\Resources;

use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\PrivilegeRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use UnexpectedValueException;

/**
 * @mixin InstitutionUser|User
 */
#[OA\Schema(
    oneOf: [
        new OA\Schema(
            description: 'Represention of an institution user',
            required: ['personalIdentificationCode', 'userId', 'institutionUserId', 'forename', 'surname', 'selectedInstitution', 'privileges'],
            properties: [
                new OA\Property(property: 'personalIdentificationCode', type: 'string'),
                new OA\Property(property: 'userId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'institutionUserId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'forename', type: 'string'),
                new OA\Property(property: 'surname', type: 'string'),
                new OA\Property(
                    property: 'selectedInstitution',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'name', type: 'string'),
                    ],
                    type: 'object'
                ),
                new OA\Property(property: 'privileges', type: 'array', items: new OA\Items(type: 'string')),
            ],
        ),
        new OA\Schema(
            description: 'Represention of an user without knowing their institution',
            required: ['personalIdentificationCode', 'userId', 'forename', 'surname'],
            properties: [
                new OA\Property(property: 'personalIdentificationCode', type: 'string'),
                new OA\Property(property: 'userId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'forename', type: 'string'),
                new OA\Property(property: 'surname', type: 'string'),
            ],
        ),
    ]
)]
class JwtClaims extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the internal object into an array of custom claims to be included in JWT.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return match (true) {
            $this->resource instanceof InstitutionUser => [
                'personalIdentificationCode' => $this->user->personal_identification_code,
                'userId' => $this->user->id,
                'institutionUserId' => $this->id,
                'forename' => $this->user->forename,
                'surname' => $this->user->surname,
                'selectedInstitution' => [
                    'id' => $this->institution_id,
                    'name' => $this->institution->name,
                ],
                'department' => [
                    'id' => $this->department->id,
                    'name' => $this->department->name,
                ],
                'privileges' => $this->institutionUserRoles
                    ->flatMap(fn (InstitutionUserRole $iuRole) => $iuRole->role?->privilegeRoles)
                    ->filter()
                    ->map(fn (PrivilegeRole $privilegeRole) => $privilegeRole->privilege?->key)
                    ->filter()
                    ->unique(),
            ],
            $this->resource instanceof User => [
                'personalIdentificationCode' => $this->personal_identification_code,
                'userId' => $this->id,
                'forename' => $this->forename,
                'surname' => $this->surname,
            ],
            default => throw new UnexpectedValueException(
                'JwtClaims has unexpected resource type: '.get_class($this->resource)
            )
        };
    }
}
