<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Http\Requests\Traits\FindsInstitutionUsersWithAnyStatus;
use App\Util\DateUtil;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

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
    private function validateInstitutionUserIsActive(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->findInstitutionUserWithAnyStatus($value)->getStatus() !== InstitutionUserStatus::Active) {
            $fail('Institution user is not active.');
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function validateDateIsNotBeforeCurrentEstonianDate(string $attribute, mixed $value, Closure $fail): void
    {
        if (DateUtil::convertStringToEstonianMidnight($value)->isBefore(DateUtil::currentEstonianDateAtMidnight())) {
            $fail('Date is earlier than the current calendar date in Estonia.');
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function validateDateIsNotAfterOneYearFromCurrentEstonianDate(string $attribute, mixed $value, Closure $fail): void
    {
        $oneYearFromNowEstonianTime = DateUtil::currentEstonianDateAtMidnight()->addYear();

        if (DateUtil::convertStringToEstonianMidnight($value)->isAfter($oneYearFromNowEstonianTime)) {
            $fail('Date is later than one year from the current calendar date in Estonia.');
        }
    }

    public function getValidatedDeactivationDateAtEstonianMidnight(): ?CarbonImmutable
    {
        if (empty($deactivationDate = $this->validated('deactivation_date'))) {
            return null;
        }

        return DateUtil::convertStringToEstonianMidnight($deactivationDate);
    }
}
