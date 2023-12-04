<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use TypeError;

class PersonalIdCodeRule implements ValidationRule
{
    private const PERSONAL_CODE_REGEX = '/^[1-6][0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])[0-9]{4}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match(self::PERSONAL_CODE_REGEX, $value)) {
            $fail('The :attribute is not valid personal ID code.');
            return;
        }

        if (! $this->isChecksumValid($value)) {
            $fail('The :attribute checksum is invalid.');
        }
    }

    private function isChecksumValid(string $code): bool
    {
        if (strlen($code) < 11) {
            return false;
        }

        $shouldBe = 0;
        try {
            // first level scale
            for ($i = 1; $i < 11; $i++) {
                $shouldBe += ($i % 10 + intval($i / 10)) * substr($code, $i - 1, 1);
            }

            $shouldBe = $shouldBe % 11;
            // teise astme skaala kui $shouldBe on võrdne 10ga
            if ($shouldBe == 10) {
                $shouldBe = 0;
                for ($i = 3; $i < 13; $i++) {
                    $shouldBe += ($i % 10 + intval($i / 10)) * substr($code, $i - 3, 1);
                }
                $shouldBe = $shouldBe % 11;
                // kui jääk on 10 siis muuda $shouldBe 0'ks
                if ($shouldBe == 10) {
                    $shouldBe = 0;
                }
            }

            return $code[10] == $shouldBe;
        } catch (TypeError) {
            return false;
        }
    }
}
