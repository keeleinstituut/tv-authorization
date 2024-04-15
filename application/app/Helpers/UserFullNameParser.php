<?php

namespace App\Helpers;

/**
 * 3 first names + last name are allowed in Estonia
 */
class UserFullNameParser
{
    public static function parse(string $name): array
    {
        $nameParts = collect(explode(' ', $name));
        $lastName = $nameParts->pop();

        return [$nameParts->implode(' '), $lastName];
    }
}
