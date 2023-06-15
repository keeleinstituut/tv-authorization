<?php

namespace App\Http\Requests;

use App\Rules\DepartmentNameNotTakenInInstitutionRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
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
                'required',
                'filled',
                new DepartmentNameNotTakenInInstitutionRule,
            ],
        ];
    }
}
