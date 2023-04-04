<?php

namespace App\Http\Controllers;

use App\Http\Resources\JwtClaims;
use App\Models\User;

class JwtClaimsSupplier extends Controller
{
    /**
     * Return custom claims (for inclusion in JWT) for the given user
     */
    public function __invoke(User $user): JwtClaims
    {
        return new JwtClaims($user);
    }
}
