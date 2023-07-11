<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumberRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'phone', type: 'string', format: 'phone', nullable: true),
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
            new OA\Property(property: 'short_name', type: 'string', format: 'phone', maxLength: 3, nullable: true),
        ]
    )
)]
class UpdateInstitutionRequest extends FormRequest
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
                'string',
                'filled',
            ],
            'phone' => [
                'nullable',
                'string',
                new PhoneNumberRule,
            ],
            'email' => [
                'nullable',
                'string',
                'email',
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:3',
            ],
        ];
    }
}
