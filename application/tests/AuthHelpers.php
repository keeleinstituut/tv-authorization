<?php

namespace Tests;

use Firebase\JWT\JWT;

trait AuthHelpers
{
    public static function generateAccessToken(array $tolkevaravPayload = [], array $generalPayload = []): string
    {
        // TODO: would be good to have full example JWT here with
        // TODO: all relevant claims to simulate real JWT.
        // TODO: This JWT should be overwrittable to support
        // TODO: different edge cases.
        $payload = collect([
            'tolkevarav' => collect([
                'userId' => 1,
                'personalIdentityCode' => '11111111111',
                'privileges' => [],
            ])->merge($tolkevaravPayload)->toArray(),
        ])->merge($generalPayload);

        return static::createJwt($payload->toArray());
    }

    private static function createJwt(array $payload): string
    {
        $privateKeyPem = static::getPrivateKey();

        return JWT::encode($payload, $privateKeyPem, 'RS256');
    }

    private static function getPrivateKey(): string
    {
        $key = env('KEYCLOAK_REALM_PRIVATE_KEY');

        return "-----BEGIN PRIVATE KEY-----\n".
            wordwrap($key, 64, "\n", true).
            "\n-----END PRIVATE KEY-----";
    }
}
