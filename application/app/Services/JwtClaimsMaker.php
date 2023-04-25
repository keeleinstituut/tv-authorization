<?php

namespace App\Services;

use App\Gates\JwtClaimsGate;
use App\Http\Resources\JwtClaims;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

readonly class JwtClaimsMaker
{
    public function __construct(private JwtClaimsGate $authorizationGate)
    {
    }

    /**
     * @param  array{institution_id: string, personal_identification_code: string}  $parameters
     *
     * @throws AuthorizationException
     */
    public function make(array $parameters): JwtClaims
    {
        Gate::allowIf($this->authorizationGate->allows());

        return new JwtClaims(
            User::wherePersonalIdentificationCode($parameters['personal_identification_code'])
                ->firstOrFail()
                ->institutionUsers()
                ->whereRelation('institution', 'id', $parameters['institution_id'])
                ->firstOrFail()
        );
    }
}
