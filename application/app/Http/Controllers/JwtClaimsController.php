<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetJwtClaimsRequest;
use App\Http\Resources\JwtClaims;
use App\Models\User;

class JwtClaimsController extends Controller
{
    /**
     * Return custom claims (for inclusion in JWT) for the given user
     */
    public function show(GetJwtClaimsRequest $request): JwtClaims
    {
        return new JwtClaims(
            User::wherePersonalIdentificationCode($request->personal_identification_code)
                ->firstOrFail()
                ->institutionUsers()
                ->whereRelation('institution', 'id', $request->institution_id)
                ->firstOrFail()
        );
    }
}
