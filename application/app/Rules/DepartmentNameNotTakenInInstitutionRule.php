<?php

namespace App\Rules;

use App\Models\Department;
use App\Policies\DepartmentPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class DepartmentNameNotTakenInInstitutionRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Department::withGlobalScope('policy', DepartmentPolicy::scope())->where('name', $value)->exists()) {
            $fail('Institution already has a department with the same name.');
        }
    }
}
