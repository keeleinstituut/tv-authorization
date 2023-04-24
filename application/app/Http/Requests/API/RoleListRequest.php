<?php

namespace App\Http\Requests\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Institution;
use Illuminate\Validation\Rule;

class RoleListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->get('institution_id') == Auth::user()->institutionId
            && Auth::hasPrivilege("VIEW_ROLE");
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            "institution_id" => [
                "required",
                'uuid',
                Rule::exists(app(Institution::class)->getTable(), 'id'),
            ],
        ];
    }
}
