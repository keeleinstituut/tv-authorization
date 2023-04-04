<?php

namespace App\Http\Resources;

use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class JwtClaims extends JsonResource
{
    /**
     * Transform the internal object into an array of custom claims to be inclued in JWT.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'personalIdentityCode' => $this->personal_identification_code,
            'forename' => $this->forename,
            'surname' => $this->surname,
            'institutions' => $this->institutionUsers
                ->map(fn (InstitutionUser $institutionUser) => $institutionUser->institution)
                ->modelKeys(),
        ];
    }
}
