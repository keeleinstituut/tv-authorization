<?php

namespace Tests\Feature;

use Tests\AuthHelpers;

readonly class DataProviders
{
    /** @return array<array{Closure(): array}> */
    public static function provideInvalidHeaderCreators(): array
    {
        return [
            'Tõlkevärav claims are empty' => [fn () => ['Authorization' => 'Bearer '.AuthHelpers::generateAccessToken()]],
            'Bearer token is blank' => [fn () => ['Authorization' => 'Bearer ']],
            'Authorization header is missing' => [fn () => []],
        ];
    }
}
