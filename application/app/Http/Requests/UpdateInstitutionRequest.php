<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumberRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
