<?php

namespace App\Http\Requests;

use App\Enums\PrivilegeKey;
use App\Models\Department;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Rules\ModelBelongsToInstitutionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * @property string forename
 * @property string surname
 * @property string email
 * @property string phone
 * @property array<string> roles
 * @property string department
 * @property string vendor
 */
class UpdateInstitutionUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(InstitutionUser::whereId($this->getInstitutionUserId())->exists(), 404);

        return filled($targetInstitutionId = $this->findInstitutionUserInstitutionId())
            && filled($tokenInstitutionId = Auth::user()?->institutionId)
            && Auth::hasPrivilege(PrivilegeKey::EditUser->value)
            && $tokenInstitutionId === $targetInstitutionId;
    }

    public function rules(): array
    {
        return [
            'user' => ['array', 'min:1'],
            'user.forename' => 'filled',
            'user.surname' => 'filled',
            'email' => 'email',
            'phone' => 'regex:/\+?[\s\d]+\d/',
            'roles' => 'array',
            'roles.*' => [
                'bail', 'uuid', $this->existsRoleInSameInstitution(),
            ],
            'department_id' => [
                'nullable', 'bail', 'uuid', $this->existsDepartmentInSameInstitution(),
            ],
        ];
    }

    private function existsRoleInSameInstitution(): ModelBelongsToInstitutionRule
    {
        return new ModelBelongsToInstitutionRule(
            Role::class,
            $this->findInstitutionUserInstitutionId()
        );
    }

    private function existsDepartmentInSameInstitution(): ModelBelongsToInstitutionRule
    {
        return new ModelBelongsToInstitutionRule(
            Department::class,
            $this->findInstitutionUserInstitutionId()
        );
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
