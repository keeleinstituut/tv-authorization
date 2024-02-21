<?php

namespace App\Http\Requests;

use App\Helpers\WorktimeValidationUtil;
use App\Http\Requests\Helpers\MaxLengthValue;
use App\Rules\PhoneNumberRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
                    new OA\Property(property: 'short_name', type: 'string', maxLength: 3),
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
                'max:'.MaxLengthValue::NAME,
            ],
            'short_name' => [
                'string',
                'filled',
                'max:3',
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
            ...WorktimeValidationUtil::buildWorktimeValidationRules(),
        ];
    }

    public function after(): array
    {
        return [
            WorktimeValidationUtil::validateAllWorktimeFieldsArePresentOrAllMissing(...),
            WorktimeValidationUtil::validateEachWorktimeStartIsBeforeEndOrBothUndefined(...),
        ];
    }
}
