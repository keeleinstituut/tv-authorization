<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

trait DataProviders
{
    /** @return array<array{Closure(): array}> */
    public static function provideInvalidHeaderCreators(): array
    {
        return [
            'Tõlkevärav claims are empty' => [fn () => ['Authorization' => 'Bearer '.self::generateAccessToken([])]],
            'Bearer token is blank' => [fn () => ['Authorization' => 'Bearer ']],
            'Authorization header is missing' => [fn () => []],
        ];
    }

    /** @return array<array{Closure(array): array, int}> */
    public static function provideRandomInstitutionUserIdInvalidator(): array
    {
        return [
            'Random UUID: institution_user_id' => [
                fn ($originalPayload) => [
                    ...$originalPayload,
                    'institution_user_id' => Str::orderedUuid()->toString(),
                ],
                Response::HTTP_NOT_FOUND,
            ],
        ];
    }
}
