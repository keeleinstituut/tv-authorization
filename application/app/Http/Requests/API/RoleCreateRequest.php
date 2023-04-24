<?php

namespace App\Http\Requests\API;

use App\Models\Institution;
use App\Models\Privilege;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class RoleCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->get('institution_id') == Auth::user()->institutionId
            && Auth::hasPrivilege("ADD_ROLE");
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
                RUle::in([Auth::user()->institutionId]),
            ],
            'privileges' => 'required|array|min:1',
            'privileges.*' => Rule::exists(app(Privilege::class)->getTable(), 'key'),
            "name" => [
                "required",
            ],
        ];
    }

    public function withValidator($validator) {
        $validator->after(function ($validator) {
            $params = collect($validator->safe());

            $afterValidator = Validator::make($params->toArray(), [
                'name' => [
                    Rule::unique(app(Role::class)->getTable(), 'name')
                        ->where(fn (Builder $query) =>
                            $query->where('institution_id', $params->get('institution_id'))
                        )
                ]
            ]);

            $afterValidator->validate();
        });
    }
}
