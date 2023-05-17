<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * 1. MUST begin with +372 country code.
 * 2. Country code MAY be followed by a single space.
 * 3. First number after the country code MUST be one of [34567].
 * 4. Ignoring country code, there MUST be 7 or 8 numbers in total.
 */
class PhoneNumberRule implements ValidationRule
{
    private const REGEX = '/^\+372 ?[34567]\d{6,7}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Str::isMatch(static::REGEX, $value)) {
            $fail('The :attribute is not valid phone number.');
        }
    }
}
