<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Models\Department;
use App\Models\Role;
use App\Rules\ModelBelongsToInstitutionRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
            'per_page' => [
                'integer',
                Rule::in([10, 50, 100]),
            ],
            'roles' => 'array',
            'roles.*' => [
                'uuid',
                $this->existsRoleInCurrentInstitution(),
            ],
            'statuses' => 'array',
            'statuses.*' => [
                'string',
                new Enum(InstitutionUserStatus::class),
            ],
            'departments' => 'array',
            'departments.*' => [
                'uuid',
                $this->existsDepartmentInCurrentInstitution(),
            ],
            'sort_by' => ['nullable', Rule::in('name', 'created_at')],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'fullname' => ['sometimes', 'string'],
        ];
    }

    private function existsDepartmentInCurrentInstitution(): ModelBelongsToInstitutionRule
    {
        return new ModelBelongsToInstitutionRule(
            Department::class,
            fn () => Auth::user()?->institutionId
        );
    }

    private function existsRoleInCurrentInstitution(): ModelBelongsToInstitutionRule
    {
        return new ModelBelongsToInstitutionRule(
            Role::class,
            fn () => Auth::user()?->institutionId
        );
    }
}
