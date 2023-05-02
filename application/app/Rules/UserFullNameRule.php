<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserFullNameRule implements ValidationRule
{
    private const USER_NAME_REGEX = '/^[a-zõäöüšž\-]+(\s[a-zõäöüšž\-]+)$/iu';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match(self::USER_NAME_REGEX, $value)) {
            $fail('The :attribute is not valid user name.');
        }
    }
}
