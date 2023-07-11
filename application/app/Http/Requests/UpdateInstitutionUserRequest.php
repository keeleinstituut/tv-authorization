<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Rules\ModelBelongsToInstitutionRule;
use App\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

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
    public function rules(): array
    {
        return [
            'user' => ['array', 'min:1'],
            'user.forename' => 'filled',
            'user.surname' => 'filled',
            'email' => 'email',
            'phone' => new PhoneNumberRule,
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
            fn () => $this->findInstitutionUserInstitutionId()
        );
    }

    private function existsDepartmentInSameInstitution(): ModelBelongsToInstitutionRule
    {
        return new ModelBelongsToInstitutionRule(
            Department::class,
            fn () => $this->findInstitutionUserInstitutionId()
        );
    }

    public function findInstitutionUserInstitutionId(): ?string
    {
        return InstitutionUser::find($this->getInstitutionUserId())?->institution_id;
    }

    public function getInstitutionUserId(): string
    {
        return $this->route('institution_user_id');
    }
}
