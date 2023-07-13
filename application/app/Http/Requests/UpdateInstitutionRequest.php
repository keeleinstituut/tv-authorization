<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumberRule;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        nullable: false,
        anyOf: [
            new OA\Schema(
                required: [
                    'worktime_timezone',
                    'monday_worktime_start',
                    'monday_worktime_end',
                    'tuesday_worktime_start',
                    'tuesday_worktime_end',
                    'wednesday_worktime_start',
                    'wednesday_worktime_end',
                    'thursday_worktime_start',
                    'thursday_worktime_end',
                    'friday_worktime_start',
                    'friday_worktime_end',
                    'saturday_worktime_start',
                    'saturday_worktime_end',
                    'sunday_worktime_start',
                    'sunday_worktime_end',
                ],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'phone', type: 'string', format: 'phone', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'short_name', type: 'string', maxLength: 3, nullable: true),
                    new OA\Property(property: 'worktime_timezone', description: 'IANA Timezone Name', type: 'string', example: 'Europe/Tallinn'),
                    new OA\Property(property: 'monday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
                    new OA\Property(property: 'monday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
                    new OA\Property(property: 'tuesday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
                    new OA\Property(property: 'tuesday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
                    new OA\Property(property: 'wednesday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
                    new OA\Property(property: 'wednesday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
                    new OA\Property(property: 'thursday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
                    new OA\Property(property: 'thursday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
                    new OA\Property(property: 'friday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
                    new OA\Property(property: 'friday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
                    new OA\Property(property: 'saturday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
                    new OA\Property(property: 'saturday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
                    new OA\Property(property: 'sunday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
                    new OA\Property(property: 'sunday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
                ],
                type: 'object'
            ),
            new OA\Schema(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'phone', type: 'string', format: 'phone', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'short_name', type: 'string', maxLength: 3, nullable: true),
                ],
                type: 'object'
            ),
        ]
    )
)]
class UpdateInstitutionRequest extends FormRequest
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
                'string',
                'filled',
            ],
            'phone' => [
                'nullable',
                'string',
                new PhoneNumberRule,
            ],
            'email' => [
                'nullable',
                'string',
                'email',
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:3',
            ],
            'worktime_timezone' => [
                'nullable',
                'required_with:'.static::getWorktimeIntervalAttributesByDay()->flatten()->join(','),
                'string',
                Rule::in(DateTimeZone::listIdentifiers()),
            ],
            ...static::buildWorktimeIntervalRules(),
        ];
    }

    /**
     * @return array<string, array>
     */
    private static function buildWorktimeIntervalRules(): array
    {
        return static::getWorktimeIntervalAttributesByDay()
            ->flatten()
            ->mapWithKeys(fn (string $attribute) => [
                $attribute => [
                    'nullable',
                    'string',
                    'date_format:H:i:s',
                ],
            ])
            ->all();
    }

    /** @return Collection<array{string, string}> */
    private static function getWorktimeIntervalAttributesByDay(): Collection
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->map(fn (string $day) => [
                "{$day}_worktime_start",
                "{$day}_worktime_end",
            ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $worktimeAttributes = static::getWorktimeIntervalAttributesByDay()
                    ->flatten()
                    ->push('worktime_timezone');

                if (! Arr::hasAny($validator->validated(), $worktimeAttributes->all())) {
                    return;
                }

                $worktimeAttributes
                    ->filter(fn (string $attribute) => $validator->safe()->missing($attribute))
                    ->each(function (string $missingAttribute) use ($validator) {
                        $validator->errors()->add(
                            $missingAttribute,
                            'Required field: if ANY worktime field is being updated, then ALL worktime fields must be sent.'
                        );
                    });
            },
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                static::getWorktimeIntervalAttributesByDay()
                    ->filter(fn (array $attributes) => Arr::hasAny($validator->validated(), $attributes))
                    ->eachSpread(function (string $startKey, string $endKey) use ($validator) {
                        $error = match (true) {
                            $this->isStartXorEndMissing($validator, $startKey, $endKey) => 'Update would result in only one of [start, end] being defined.',
                            $this->isEndBeforeOrEqualToStart($validator, $startKey, $endKey) => 'Update would result in end being before or equal to start.',
                            default => null
                        };

                        if (empty($error)) {
                            return;
                        }

                        $validator->errors()
                            ->addIf($validator->safe()->has($startKey), $startKey, $error)
                            ->addIf($validator->safe()->has($endKey), $endKey, $error);
                    });
            },
        ];
    }

    private function isStartXorEndMissing(Validator $validator, string $startKey, string $endKey): bool
    {
        $start = $validator->safe()->offsetGet($startKey);
        $end = $validator->safe()->offsetGet($endKey);

        return empty($start) xor empty($end);
    }

    private function isEndBeforeOrEqualToStart(Validator $validator, string $startKey, string $endKey): bool
    {
        $start = $validator->safe()->offsetGet($startKey);
        $end = $validator->safe()->offsetGet($endKey);
        $timezone = $validator->safe()->offsetGet('worktime_timezone');

        if (empty($start) || empty($end)) {
            return false;
        }

        return ! Date::parse($end, tz: $timezone)->isAfter(Date::parse($start, tz: $timezone));
    }
}
