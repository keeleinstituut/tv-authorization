<?php

namespace Tests;

use App\Enums\PrivilegeKey;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;

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
            ])->mergeRecursive($tolkevaravPayload)->toArray(),
        ])->mergeRecursive($generalPayload);

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

    /**
     * @param  array<PrivilegeKey>  $privileges
     */
    public function createJsonHeaderWithTokenParams(string $institutionId, array $privileges): array
    {
        $defaultToken = $this->generateAccessToken([
            'selectedInstitution' => ['id' => $institutionId],
            'privileges' => Arr::map($privileges, fn ($privilege) => $privilege->value),
        ]);

        return [
            'Authorization' => "Bearer $defaultToken",
            'Accept' => 'application/json',
        ];
    }
}
