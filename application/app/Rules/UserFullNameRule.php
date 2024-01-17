<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UserFullNameRule implements ValidationRule
{
    /** @var string  3 first names + last name are allowed in Estonia */
    private const REGEX = '/^[\p{L}\-\']+(\s[\p{L}\-\']+)?(\s[\p{L}\-\']+)?\s[\p{L}\-\']+$/u';

    const MAX_LENGTH = 255;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Str::match(self::REGEX, $value)) {
            $fail('The user name is invalid.');
        }

        if (Str::length($value) > self::MAX_LENGTH) {
            $fail('The user name is too long. Max available length is ' . self::MAX_LENGTH);
        }
    }
}
