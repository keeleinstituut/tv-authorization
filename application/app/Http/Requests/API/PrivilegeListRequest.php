<?php

namespace App\Http\Requests\API;

use App\Enums\PrivilegeKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PrivilegeListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::ViewRole->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
