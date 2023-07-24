<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Models\Department;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Rules\ModelBelongsToInstitutionRule;
use App\Rules\PhoneNumberRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property string forename
 * @property string surname
 * @property string email
 * @property string phone
 * @property array<string> roles
 * @property string department
 * @property string vendor
 */
#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(
                property: 'user',
                minProperties: 1,
                properties: [
                    new OA\Property(property: 'forename', type: 'string'),
                    new OA\Property(property: 'surname', type: 'string'),
                ],
                type: 'object'
            ),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'phone', type: 'string', format: 'phone'),
            new OA\Property(
                property: 'roles',
                type: 'array',
                items: new OA\Items(type: 'string', format: 'uuid')
            ),
            new OA\Property(property: 'department_id', type: 'string', format: 'uuid', nullable: true),
        ]
    )
)]
class UpdateInstitutionUserRequest extends FormRequest
{
    private ?InstitutionUser $targetInstitutionUser = null;

    public function rules(): array
    {
        return [
            'user' => ['array', 'min:1'],
            'user.forename' => 'filled',
            'user.surname' => 'filled',
            'email' => 'email',
            'phone' => new PhoneNumberRule,
            'roles' => [
                'array',
                $this->validateTargetInstitutionUserIsActive(...),
            ],
            'roles.*' => [
                'bail', 'uuid', $this->existsRoleInSameInstitution(),
            ],
            'department_id' => [
                'nullable', 'bail', 'uuid', $this->existsDepartmentInSameInstitution(),
            ],
        ];
    }

    private function validateTargetInstitutionUserIsActive(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->obtainInstitutionUser()->getStatus() !== InstitutionUserStatus::Active) {
            $fail('Institution user is not active. To modify roles, user must be active.');
        }
    }

    private function existsRoleInSameInstitution(): ModelBelongsToInstitutionRule
    {
        return new ModelBelongsToInstitutionRule(
            Role::class,
            fn () => $this->obtainInstitutionUser()->institution_id
        );
    }

    private function existsDepartmentInSameInstitution(): ModelBelongsToInstitutionRule
    {
        return new ModelBelongsToInstitutionRule(
            Department::class,
            fn () => $this->obtainInstitutionUser()->institution_id
        );
    }

    public function obtainInstitutionUser(): InstitutionUser
    {
        if (empty($this->targetInstitutionUser)) {
            $this->targetInstitutionUser = InstitutionUser::withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
                ->find($this->getInstitutionUserId());

            abort_if(empty($this->targetInstitutionUser), Response::HTTP_NOT_FOUND);
        }

        return $this->targetInstitutionUser;
    }

    public function getInstitutionUserId(): string
    {
        return $this->route('institution_user_id');
    }
}
