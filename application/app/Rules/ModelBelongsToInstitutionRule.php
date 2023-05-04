<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

readonly class ModelBelongsToInstitutionRule implements ValidationRule
{
    public function __construct(private string $modelClassName, private string $expectedInstitutionId)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->expectedInstitutionId)
            || ! class_exists($this->modelClassName)
            || ! ($model = new $this->modelClassName)
            || ! ($model instanceof Model)
            || ! ($actualInstitutionId = $model::find($value)?->institution_id)
            || empty($actualInstitutionId)
            || $actualInstitutionId !== $this->expectedInstitutionId
        ) {
            $fail('Unable to ensure that the target object belongs to expected institution.');
        }
    }
}
