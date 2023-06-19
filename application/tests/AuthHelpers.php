<?php

namespace Tests;

use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\PrivilegeRole;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

readonly class AuthHelpers
{
    public static function generateAccessTokenForInstitutionUser(
        InstitutionUser $institutionUser,
        array $tolkevaravClaimOverrides = [],
        string $azp = null): string
    {
        return AuthHelpers::generateAccessToken([
            ...AuthHelpers::makeTolkevaravClaimsForInstitutionUser($institutionUser),
            ...$tolkevaravClaimOverrides,
        ], $azp);
    }

    /** @return array{
     *     personalIdentificationCode: string,
     *     userId: string,
     *     institutionUserId: string,
     *     forename: string,
     *     surname: string,
     *     selectedInstitution: array{
     *         id: string,
     *         name: string
     *     },
     *     privileges: array<string>
     * } */
    public static function makeTolkevaravClaimsForInstitutionUser(InstitutionUser $institutionUser): array
    {
        return [
            'personalIdentificationCode' => $institutionUser->user->personal_identification_code,
            'userId' => $institutionUser->user->id,
            'institutionUserId' => $institutionUser->id,
            'forename' => $institutionUser->user->forename,
            'surname' => $institutionUser->user->surname,
            'selectedInstitution' => [
                'id' => $institutionUser->institution->id,
                'name' => $institutionUser->institution->name,
            ],
            'privileges' => $institutionUser->institutionUserRoles
                ->flatMap(fn (InstitutionUserRole $iuRole) => $iuRole->role?->privilegeRoles)
                ->filter()
                ->map(fn (PrivilegeRole $privilegeRole) => $privilegeRole->privilege?->key?->value)
                ->filter()
                ->unique(),
        ];
    }

    /** @return array{
     *     personalIdentificationCode: string,
     *     userId: string,
     *     forename: string,
     *     surname: string,
     * } */
    public static function makeTolkevaravClaimsForUser(User $user): array
    {
        return [
            'personalIdentificationCode' => $user->personal_identification_code,
            'userId' => $user->id,
            'forename' => $user->forename,
            'surname' => $user->surname,
        ];
    }

    public static function createHeadersForInstitutionUser(InstitutionUser $institutionUser): array
    {
        $accessToken = self::generateAccessToken(
            self::makeTolkevaravClaimsForInstitutionUser($institutionUser)
        );

        return [
            'Authorization' => "Bearer $accessToken",
        ];
    }

    public static function generateAccessToken(array $tolkevaravPayload = [], string $azp = null): string
    {
        // TODO: would be good to have full example JWT here with
        // TODO: all relevant claims to simulate real JWT.
        // TODO: This JWT should be overwrittable to support
        // TODO: different edge cases.
        $payload = collect([
            'azp' => $azp ?? Str::of(config('keycloak.accepted_authorized_parties'))
                ->explode(',')
                ->first(),
            'iss' => config('keycloak.base_url').'/realms/'.config('keycloak.realm'),
            'tolkevarav' => $tolkevaravPayload,
        ]);

        return static::createJwt($payload->toArray());
    }

    public static function createJwt(array $payload): string
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
