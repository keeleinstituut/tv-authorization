<?php

namespace App\Gates;

use KeycloakAuthGuard\Services\Decoders\RequestBasedJwtTokenDecoder;
use stdClass;

readonly class JwtClaimsGate
{
    public function __construct(private RequestBasedJwtTokenDecoder $jwtDecoder)
    {
    }

    public function allows(): bool
    {
        return $this->isAuthorizedPartySsoInternalClient(
            $this->jwtDecoder->getDecodedJwtWithSpecifiedValidation(false, true)
        );
    }

    public function isAuthorizedPartySsoInternalClient(?stdClass $decodedJwt): bool
    {
        return filled($decodedJwt)
            && property_exists($decodedJwt, 'azp')
            && filled($tokenAuthorizedParty = $decodedJwt->azp)
            && filled($expectedSsoInternalClientId = config('api.sso_internal_client_id'))
            && $tokenAuthorizedParty === $expectedSsoInternalClientId;
    }
}
