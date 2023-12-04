<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UserFullNameRule implements ValidationRule
{
    /** @var string  3 first names + last name are allowed in Estonia */
    private const REGEX = '/^[\p{L}\-\']+(\s[\p{L}\-\']+)?(\s[\p{L}\-\']+)?\s[\p{L}\-\']+$/u';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Str::match(self::REGEX, $value)) {
            $fail('The :attribute is not valid user name.');
        }
    }
}
