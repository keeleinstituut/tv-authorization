<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberRule implements ValidationRule
{
    private const ESTONIAN_PHONE_REGEX = '/^\+372 5\d{6,7}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match(self::ESTONIAN_PHONE_REGEX, $value)) {
            $fail('The :attribute is not valid phone number.');
        }
    }
}
