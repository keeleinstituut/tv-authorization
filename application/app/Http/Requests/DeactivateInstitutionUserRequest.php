<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Http\Requests\Traits\FindsInstitutionUsersWithAnyStatus;
use App\Util\DateUtil;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        required: ['institution_user_id', 'deactivation_date'],
        properties: [
            new OA\Property(
                property: 'institution_user_id',
                description: 'UUID of institution user whose deactivation date is being set',
                type: 'string',
                format: 'uuid'
            ),
            new OA\Property(property: 'deactivation_date', type: 'string', format: 'date'),
        ]
    )
)]
class DeactivateInstitutionUserRequest extends FormRequest
{
    use FindsInstitutionUsersWithAnyStatus;

    public function rules(): array
    {
        return [
            'institution_user_id' => [
                'bail',
                'required',
                'uuid',
                $this->validateInstitutionUserIsActive(...),
                $this->validateInstitutionUserIsNotOnlyUserWithRootRole(...),
            ],
            'deactivation_date' => [
                'bail',
                'present',
                'nullable',
                'date_format:Y-m-d',
                $this->validateDateIsNotBeforeCurrentEstonianDate(...),
                $this->validateDateIsNotAfterOneYearFromCurrentEstonianDate(...),
            ],
        ];
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function validateInstitutionUserIsActive(
        string $attribute, mixed $value, Closure $fail
    ): void {
        if ($this->findInstitutionUserWithAnyStatus($value)->getStatus() !== InstitutionUserStatus::Active) {
            $fail('Institution user is not active.');
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function validateInstitutionUserIsNotOnlyUserWithRootRole(
        string $attribute, mixed $value, Closure $fail
    ): void {
        if ($this->findInstitutionUserWithAnyStatus($value)->isOnlyUserWithRootRole()) {
            $fail('This is the only user with a root role in the institution.');
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function validateDateIsNotBeforeCurrentEstonianDate(string $attribute,
        string $value,
        Closure $fail): void
    {
        if (static::convertDateStringToEstonianMidnight($value)->isBefore(Date::today(DateUtil::ESTONIAN_TIMEZONE))) {
            $fail('Date is earlier than the current calendar date in Estonia.');
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function validateDateIsNotAfterOneYearFromCurrentEstonianDate(string $attribute,
        string $value,
        Closure $fail): void
    {
        $oneYearFromNowEstonianTime = Date::today(DateUtil::ESTONIAN_TIMEZONE)->addYear();

        if (static::convertDateStringToEstonianMidnight($value)->isAfter($oneYearFromNowEstonianTime)) {
            $fail('Date is later than one year from the current calendar date in Estonia.');
        }
    }

    public function getValidatedDeactivationDateAtEstonianMidnight(): ?CarbonImmutable
    {
        if (empty($deactivationDate = $this->validated('deactivation_date'))) {
            return null;
        }

        return static::convertDateStringToEstonianMidnight($deactivationDate)->toImmutable();
    }

    private static function convertDateStringToEstonianMidnight(string $dateString): CarbonInterface
    {
        return Date::parse($dateString, DateUtil::ESTONIAN_TIMEZONE);
    }
}
