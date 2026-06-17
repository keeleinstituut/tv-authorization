<?php

namespace App\Rules;

use App\Enums\PrivilegeKey;
use App\Models\AuthUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TranslationAgencyAllowedPrivilegesRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = request()->user();

        if (! $user instanceof AuthUser || ! $user->belongsToTranslationAgency()) {
            return;
        }

        if (! is_array($value)) {
            return;
        }

        $allowedValues = array_map(
            fn (PrivilegeKey $key) => $key->value,
            PrivilegeKey::TRANSLATION_AGENCY_ALLOWED_PRIVILEGES
        );

        if (array_diff($value, $allowedValues)) {
            $fail('Tõlkebüroo institutsioonile ei ole see õigus lubatud.');
        }
    }
}
