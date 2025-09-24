<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;

class DepartmentBulkUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.*.name' => 'required|string',
            'data.*.id' => [
                'uuid',
                Rule::exists(app(Department::class)->getTable(), 'id'),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                $newNames = collect([]);

                // Iterate over parameter array and check "name" uniqueness for
                // new department entries
                collect($this->data)->each(function ($elem, $index) use ($validator, $newNames) {
                    $id = data_get($elem, 'id');
                    $name = data_get($elem, 'name');

                    if (!$id) {
                        $exists = $newNames->contains($name) || Department::getModel()
                            ->where('institution_id', Auth::user()->institutionId)
                            ->where('name', $name)
                            ->exists();

                        if ($exists) {
                            $attributeName = "data.$index.name";
                            $validator->errors()->add(
                                $attributeName,
                                __('validation.unique', ['attribute' => $attributeName])
                            );
                        } else {
                            $newNames->push($name);
                        }
                    }
                });
            }
        ];
    }
}
