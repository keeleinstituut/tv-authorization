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
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        required: ['personal_identification_code', 'name', 'phone', 'email', 'role'],
        properties: [
            new OA\Property(property: 'personal_identification_code', type: 'string'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'phone', type: 'string', format: 'phone'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'department', type: 'string', nullable: true),
            new OA\Property(property: 'role', type: 'string', example: 'Tõlk,Tõlkekorraldaja,Peakasutaja'),
        ]
    )
)]
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
