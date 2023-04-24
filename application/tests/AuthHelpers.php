<?php

namespace Tests;

use Firebase\JWT\JWT;

trait AuthHelpers
{
    public static function generateAccessToken($tolkevaravPayload = []) {
        // TODO: would be good to have full example JWT here with
        // TODO: all relevant claims to simulate real JWT.
        // TODO: This JWT should be overwrittable to support
        // TODO: different edge cases.
        $payload = [
            'tolkevarav' => collect([
                'userId' => 1,
                'personalIdentityCode' => '11111111111',
                'privileges' => [],
            ])->mergeRecursive($tolkevaravPayload)->toArray(),
        ];
        return static::createJwt($payload);
    }

    private static function createJwt($payload) {
        $privateKeyPem = static::getPrivateKey();
        return JWT::encode($payload, $privateKeyPem, 'RS256');
    }

    private static function getPrivateKey(): string
    {
        $key = env('KEYCLOAK_REALM_PRIVATE_KEY');

        return "-----BEGIN PRIVATE KEY-----\n" .
            wordwrap($key, 64, "\n", true) .
            "\n-----END PRIVATE KEY-----";
    }
}