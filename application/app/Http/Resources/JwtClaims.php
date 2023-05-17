<?php

namespace App\Http\Resources;

use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\PrivilegeRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InstitutionUser
 */
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
        return [
            'personalIdentificationCode' => $this->user->personal_identification_code,
            'userId' => $this->user->id,
            'institutionUserId' => $this->id,
            'forename' => $this->user->forename,
            'surname' => $this->user->surname,
            'selectedInstitution' => [
                'id' => $this->institution_id,
                'name' => $this->institution->name,
            ],
            'privileges' => $this->institutionUserRoles
                ->flatMap(fn (InstitutionUserRole $institutionUserRole) => $institutionUserRole->role?->privilegeRoles)
                ->filter()
                ->map(fn (PrivilegeRole $privilegeRole) => $privilegeRole->privilege?->key)
                ->filter()
                ->unique(),
        ];
    }
}
