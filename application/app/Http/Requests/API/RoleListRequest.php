<?php

namespace App\Http\Requests\API;

use App\Models\Institution;
use App\Enums\PrivilegeKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RoleListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'institution_id' => [
                'uuid',
                Rule::exists(app(Institution::class)->getTable(), 'id'),
            ],
        ];
    }
}
