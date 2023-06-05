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
