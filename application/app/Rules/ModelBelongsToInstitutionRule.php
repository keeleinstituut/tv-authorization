<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

readonly class ModelBelongsToInstitutionRule implements ValidationRule
{
    private Model $model;

    private Closure $expectedInstitutionIdRetriever;

    public function __construct(string $modelClassName, Closure $expectedInstitutionIdRetriever)
    {
        if (! class_exists($modelClassName)
            || ! ($model = new $modelClassName)
            || ! ($model instanceof Model)) {
            throw new InvalidArgumentException("Rule constructed with an in invalid model class name: $modelClassName");
        }

        $this->model = $model;
        $this->expectedInstitutionIdRetriever = $expectedInstitutionIdRetriever;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($foundModelInstance = $this->model::find($value))) {
            $fail('Did not find an instance of '.gettype($this->model).' with id '.$value);

            return;
        }

        if (empty($actualInstitutionId = $foundModelInstance->institution_id)) {
            $fail("Found instance of model (with key $foundModelInstance->key) but it did not have an institution id");

            return;
        }

        if (empty($expectedInstitutionId = $this->expectedInstitutionIdRetriever->__invoke())) {
            $fail('The expected institution_id provided by callback was empty');

            return;
        }

        if ($expectedInstitutionId !== $actualInstitutionId) {
            $fail(
                'Actual institution_id did not match expected institutuion id. '.
                "Expected '$expectedInstitutionId', but was '$actualInstitutionId'"
            );
        }
    }
}
