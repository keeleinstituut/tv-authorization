<?php

namespace App\Http\Requests\API;

use App\Models\Institution;
use App\Models\Privilege;
use App\Models\Role;
use App\Enums\PrivilegeKey;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $role = Role::find($this->route('role_id'));

        return $role
            && $role->institution_id == Auth::user()->institutionId
            && Auth::hasPrivilege(PrivilegeKey::EditRole->value);
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
                Rule::in([Auth::user()->institutionId]),
            ],
            'privileges' => 'array|min:1',
            'privileges.*' => Rule::exists(app(Privilege::class)->getTable(), 'key'),
            'name' => 'string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $params = collect($validator->safe());

            $afterValidator = Validator::make($params->toArray(), [
                'name' => [
                    Rule::unique(app(Role::class)->getTable(), 'name')
                        ->where(fn (Builder $query) => $query
                                ->where('institution_id', $params->get('institution_id'))
                                ->whereNull('deleted_at')
                        )
                        ->ignore($this->route('role_id')),
                ],
            ]);

            $afterValidator->validate();
        });
    }
}
