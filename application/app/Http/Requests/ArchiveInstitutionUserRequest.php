<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Http\Requests\Traits\FindsInstitutionUsersWithAnyStatus;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ArchiveInstitutionUserRequest extends FormRequest
{
    use FindsInstitutionUsersWithAnyStatus;

    public function rules(): array
    {
        return [
            'institution_user_id' => [
                'bail',
                'required',
                'uuid',
                $this->validateInstitutionUserIsNotArchived(...),
            ],
        ];
    }

    private function validateInstitutionUserIsNotArchived(
        string $attribute, mixed $value, Closure $fail
    ): void {
        if ($this->findInstitutionUserWithAnyStatus($value)->getStatus() === InstitutionUserStatus::Archived) {
            $fail('Institution users is already archived.');
        }
    }
}
