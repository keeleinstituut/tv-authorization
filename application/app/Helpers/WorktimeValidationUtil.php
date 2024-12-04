<?php

namespace App\Helpers;

use DateTimeZone;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WorktimeValidationUtil
{
    /**
     * @return array<string, array>
     */
    public static function buildWorktimeValidationRules(): array
    {
        return [
            'worktime_timezone' => [
                'nullable',
                'required_with:'.static::getWorktimeIntervalEdgesByDay()->flatten()->join(','),
                'string',
                Rule::in(DateTimeZone::listIdentifiers()),
            ],
            ...static::buildOnlyWorktimeIntervalValidationRules(),
        ];
    }

    public static function validateEachWorktimeStartIsBeforeEndOrBothUndefined(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        static::getWorktimeIntervalEdgesByDay()
            ->filter(fn (array $attributes) => Arr::hasAny($validator->validated(), $attributes))
            ->eachSpread(function (string $startKey, string $endKey) use ($validator) {
                $error = match (true) {
                    static::isStartXorEndMissing($validator, $startKey, $endKey) => 'Either only \'start\' or only \'end\' was defined for this day.',
                    static::isEndBeforeOrEqualToStart($validator, $startKey, $endKey) => 'Working time \'end\' is before or equal to its \'start\'.',
                    default => null
                };

                if (empty($error)) {
                    return;
                }

                $validator->errors()
                    ->addIf($validator->safe()->has($startKey), $startKey, $error)
                    ->addIf($validator->safe()->has($endKey), $endKey, $error);
            });
    }

    public static function validateAllWorktimeFieldsArePresentOrAllMissing(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $worktimeAttributes = self::getWorktimeAttributes();

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
    }

    /** @return Collection<array{string, string}> */
    public static function getWorktimeIntervalEdgesByDay(): Collection
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->map(fn (string $day) => [
                "{$day}_worktime_start",
                "{$day}_worktime_end",
            ]);
    }

    public static function getWorktimeAttributes(): Collection
    {
        return WorktimeValidationUtil::getWorktimeIntervalEdgesByDay()
            ->flatten()
            ->push('worktime_timezone');
    }

    /**
     * @return array<string, array>
     */
    private static function buildOnlyWorktimeIntervalValidationRules(): array
    {
        return WorktimeValidationUtil::getWorktimeIntervalEdgesByDay()
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

    private static function isStartXorEndMissing(Validator $validator, string $startKey, string $endKey): bool
    {
        $start = $validator->safe()->offsetGet($startKey);
        $end = $validator->safe()->offsetGet($endKey);

        return empty($start) xor empty($end);
    }

    private static function isEndBeforeOrEqualToStart(Validator $validator, string $startKey, string $endKey): bool
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
