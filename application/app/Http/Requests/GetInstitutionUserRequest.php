<?php

namespace App\Http\Requests;

use App\Enums\PrivilegeKey;
use App\Models\InstitutionUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class GetInstitutionUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(InstitutionUser::whereId($this->getInstitutionUserId())->exists(), 404);

        return filled($targetInstitutionId = $this->findInstitutionUserInstitutionId())
            && filled($tokenInstitutionId = Auth::user()?->institutionId)
            && Auth::hasPrivilege(PrivilegeKey::ViewUser->value)
            && $tokenInstitutionId === $targetInstitutionId;
    }

    public function findInstitutionUserInstitutionId(): ?string
    {
        return InstitutionUser::find($this->getInstitutionUserId())?->institution_id;
    }

    public function getInstitutionUserId(): string
    {
        return $this->route('institutionUserId');
    }
}
