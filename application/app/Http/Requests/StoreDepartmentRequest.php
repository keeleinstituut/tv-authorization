<?php

namespace App\Http\Requests;

use App\Http\Requests\Helpers\MaxLengthValue;
use App\Rules\DepartmentNameNotTakenInInstitutionRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(property: 'name', type: 'string'),
        ]
    )
)]
class StoreDepartmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'filled',
                new DepartmentNameNotTakenInInstitutionRule,
                'max:'.MaxLengthValue::NAME,
            ],
        ];
    }
}
