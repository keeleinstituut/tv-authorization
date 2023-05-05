<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string personal_identification_code
 * @property string institution_id
 */
class GetJwtClaimsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'personal_identification_code' => ['string', 'required'],
            'institution_id' => ['string', 'required', 'uuid'],
        ];
    }
}
