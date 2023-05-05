<?php

namespace App\Http\Requests\API;

use App\Models\Institution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleListRequest extends FormRequest
{
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
