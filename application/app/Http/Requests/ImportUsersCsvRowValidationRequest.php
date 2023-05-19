<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Role;
use App\Policies\DepartmentPolicy;
use App\Policies\RolePolicy;
use App\Rules\PersonalIdCodeRule;
use App\Rules\PhoneNumberRule;
use App\Rules\UserFullNameRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportUsersCsvRowValidationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'personal_identification_code' => ['required', new PersonalIdCodeRule],
            'name' => ['required', new UserFullNameRule],
            'phone' => ['required', new PhoneNumberRule],
            'email' => ['required', 'email'],
            'department' => ['nullable', 'string',
                function ($attribute, $value, $fail) {
                    $exists = Department::query()->withGlobalScope('policy', DepartmentPolicy::scope())
                        ->where('name', $value)->exists();

                    if (! $exists) {
                        $fail("The department with the name '$value' does not exist.");
                    }
                },
            ],
            'role' => ['required', 'string',
                function ($attribute, $value, $fail) {
                    $names = explode(',', $value);
                    foreach ($names as $name) {
                        $name = trim($name);
                        if (empty($name)) {
                            $fail("The role with the name '$name' does not exist.");
                        }

                        $exists = Role::query()->withGlobalScope('policy', RolePolicy::scope())
                            ->where('name', $name)
                            ->exists();

                        if (! $exists) {
                            $fail("The role with the name '$name' does not exist.");
                        }
                    }
                },
            ],
        ];
    }
}
