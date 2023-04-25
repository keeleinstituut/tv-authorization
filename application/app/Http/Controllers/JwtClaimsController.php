<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetJwtClaimsRequest;
use App\Http\Resources\JwtClaims;
use App\Services\JwtClaimsMaker;
use Illuminate\Auth\Access\AuthorizationException;

class JwtClaimsController extends Controller
{
    public function __construct(private readonly JwtClaimsMaker $claimsMaker)
    {
    }

    /**
     * Return custom claims (for inclusion in JWT) for the given user
     *
     * @throws AuthorizationException
     */
    public function show(GetJwtClaimsRequest $request): JwtClaims
    {
        return $this->claimsMaker->make($request->validated());
    }
}
