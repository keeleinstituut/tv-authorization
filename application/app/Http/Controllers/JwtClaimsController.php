<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetJwtClaimsRequest;
use App\Http\Resources\JwtClaims;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use KeycloakAuthGuard\Services\Decoders\RequestBasedJwtTokenDecoder;
use stdClass;

class JwtClaimsController extends Controller
{
    public function __construct(private readonly RequestBasedJwtTokenDecoder $jwtDecoder)
    {
    }

    /**
     * Return custom claims (for inclusion in JWT) for the given user
     *
     * @throws AuthorizationException
     */
    public function show(GetJwtClaimsRequest $request): JwtClaims
    {
        Gate::allowIf($this->isAuthorized());

        $user = User::wherePersonalIdentificationCode($request->validated('personal_identification_code'))
            ->firstOrFail();

        if ($request->has('institution_id')) {
            $institutionUser = $user->institutionUsers()->whereRelation(
                'institution',
                'id',
                $request->validated('institution_id')
            )->first();

            abort_if(empty($institutionUser), 403);

            return new JwtClaims($institutionUser);
        }

        return new JwtClaims($user);
    }

    private function isAuthorized(): bool
    {
        $decodedJwt = $this->jwtDecoder->getDecodedJwtWithSpecifiedValidation(false, false);

        abort_if(empty($decodedJwt), 401);

        return $this->isAuthorizedPartySsoInternalClient($decodedJwt);

    }

    private function isAuthorizedPartySsoInternalClient(stdClass $decodedJwt): bool
    {
        return property_exists($decodedJwt, 'azp')
            && filled($tokenAuthorizedParty = $decodedJwt->azp)
            && filled($expectedSsoInternalClientId = config('api.sso_internal_client_id'))
            && $tokenAuthorizedParty === $expectedSsoInternalClientId;
    }
}
