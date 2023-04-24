<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string personal_identification_code
 * @property string institution_id
 */
class GetJwtClaimsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Only authorize if request sent from expected Keycloak instance
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'personal_identification_code' => ['string', 'required'],
            'institution_id' => ['string', 'required', 'uuid'],
        ];
    }
}
