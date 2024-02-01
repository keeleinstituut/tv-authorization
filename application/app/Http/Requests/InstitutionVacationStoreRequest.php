<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        required: ['start_date', 'end_date'],
        properties: [
            new OA\Property(property: 'start_date', type: 'string', format: 'date'),
            new OA\Property(property: 'end_date', type: 'string', format: 'date'),
        ]
    )
)]
class InstitutionVacationStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'gte:start_date']
        ];
    }
}
