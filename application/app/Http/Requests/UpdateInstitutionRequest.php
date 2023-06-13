<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumberRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
                Rule::when(
                    $this->input('phone') !== null,
                    ['string', 'filled', new PhoneNumberRule]
                ),
            ],
            'email' => [
                'nullable',
                Rule::when(
                    $this->input('email') !== null,
                    ['string', 'filled', 'email']
                ),
            ],
            'short_name' => [
                'nullable',
                Rule::when(
                    $this->input('short_name') !== null,
                    ['string', 'filled', 'max:3']
                ),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny(['name', 'phone', 'email', 'short_name'])) {
                    $validator->errors()->add('.', 'At least one field to update is required.');
                }
            },
        ];
    }
}
