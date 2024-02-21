<?php

namespace App\Http\Requests;

use App\Models\InstitutionUserVacation;
use App\Models\InstitutionVacation;
use App\Policies\InstitutionUserVacationPolicy;
use App\Policies\InstitutionVacationPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        required: ['vacations', 'institution_user_id'],
        properties: [
            new OA\Property(property: 'institution_user_id', type: 'string', format: 'uuid'),
            new OA\Property(
                property: 'vacations',
                type: 'array',
                items: new OA\Items(
                    required: ['start_date', 'end_date'],
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
                    ],
                    type: 'object'
                ),
            ),
            new OA\Property(
                property: 'institution_vacation_exclusions',
                type: 'array',
                items: new OA\Items(type: 'string', format: 'uuid')
            ),
        ]
    )
)]
class InstitutionUserVacationSyncRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'institution_user_id' => ['required', 'uuid'],
            'vacations' => ['present', 'array', 'max:10000'],
            'vacations.*.id' => ['sometimes', 'nullable', 'uuid'],
            'vacations.*.start_date' => ['required', 'date_format:Y-m-d'],
            'vacations.*.end_date' => ['required', 'date_format:Y-m-d'],
            'institution_vacation_exclusions' => ['present', 'array', 'max:10000'],
            'institution_vacation_exclusions.*' => ['required', 'uuid']
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $vacationsUniqueMap = collect();
                collect($this->validated('vacations'))->each(function (array $vacationData, int $idx) use ($validator, $vacationsUniqueMap) {
                    if (filled($id = data_get($vacationData, 'id'))) {
                        if (! $this->vacationExists($id)) {
                            $validator->errors()->add("vacations.$idx.id", 'Vacation with such ID does not exist');
                            return;
                        }
                    } elseif ($this->sameVacationExists(data_get($vacationData, 'start_date'), data_get($vacationData, 'end_date'))) {
                        $validator->errors()->add("vacations.$idx.start_date", 'The same vacation already exists');
                        return;
                    }

                    if ($vacationsUniqueMap->has($this->getVacationUid($vacationData))) {
                        $validator->errors()->add("vacations.$idx.start_date", 'Trying to create two equal vacations');
                        return;
                    }
                    $vacationsUniqueMap->put($this->getVacationUid($vacationData), true);

                    if (data_get($vacationData, 'start_date') > data_get($vacationData, 'end_date')) {
                        $validator->errors()->add("vacations.$idx.start_date", 'The start date should be less or equal to the end date');
                    }
                });

                $institutionVacationExclusions = $this->validated('institution_vacation_exclusions');
                if (filled($institutionVacationExclusions)) {
                    $existingCount = InstitutionVacation::query()->withGlobalScope('policy', InstitutionVacationPolicy::scope())
                        ->whereIn('id', $this->validated('institution_vacation_exclusions'))
                        ->count();

                    if ($existingCount !== count($institutionVacationExclusions)) {
                        $validator->errors()->add('institution_vacation_exclusions', 'Invalid institution vacation exclusions passed');
                    }
                }
            }
        ];
    }

    private function vacationExists(string $id): bool
    {
        return InstitutionUserVacation::getModel()
            ->withGlobalScope('policy', InstitutionUserVacationPolicy::scope())
            ->where('id', $id)
            ->exists();
    }

    private function sameVacationExists(string $startDate, string $endDate): bool
    {
        return InstitutionUserVacation::getModel()
            ->withGlobalScope('policy', InstitutionUserVacationPolicy::scope())
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->where('institution_user_id', $this->input('institution_user_id'))
            ->exists();
    }

    private function getVacationUid(array $vacationData): string
    {
        return join('_', [
            data_get($vacationData, 'start_date'),
            data_get($vacationData, 'end_date'),
        ]);
    }
}
