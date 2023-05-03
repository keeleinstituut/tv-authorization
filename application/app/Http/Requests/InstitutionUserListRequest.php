<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Models\Role;
use App\Policies\Scopes\RoleScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class InstitutionUserListRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['integer', Rule::in([10, 50, 100])],
            'role_id' => ['uuid', function ($attribute, $value, $fail) {
                if (! Role::where('id', $value)->withGlobalScope('auth', new RoleScope)->exists()) {
                    $fail("The selected $attribute is invalid.");
                }
            }],
            'status' => ['string', new Enum(InstitutionUserStatus::class)],
            'sort_by' => ['nullable', Rule::in('name', 'created_at')],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'department' => ['string'], // TODO: implement after adding tags into the system.
        ];
    }
}
