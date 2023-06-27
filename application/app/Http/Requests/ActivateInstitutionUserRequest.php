<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Http\Requests\Traits\FindsInstitutionUsersWithAnyStatus;
use App\Http\Resources\API\RoleResource;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Rules\ModelBelongsToInstitutionRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        required: ['institution_user_id', 'notify_user', 'roles'],
        properties: [
            new OA\Property(
                property: 'institution_user_id',
                description: 'UUID of institution user to reactivate',
                type: 'string',
                format: 'uuid'
            ),
            new OA\Property(property: 'notify_user', type: 'boolean'),
            new OA\Property(
                property: 'roles',
                type: 'array',
                items: new OA\Items(ref: RoleResource::class),
                minItems: 1
            ),
        ]
    )
)]
class ActivateInstitutionUserRequest extends FormRequest
{
    use FindsInstitutionUsersWithAnyStatus;

    public function rules(): array
    {
        return [
            'institution_user_id' => [
                'bail',
                'required',
                'uuid',
                $this->validateInstitutionUserIsDeactivated(...),
            ],
            'notify_user' => [
                'required',
                'boolean',
            ],
            'roles' => [
                'array',
                'required',
                'min:1',
            ],
            'roles.*' => [
                'bail',
                'uuid',
                $this->institutionUserIdIsValid(...),
                $this->existsRoleInSameInstitution(),
            ],
        ];
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function validateInstitutionUserIsDeactivated(string $attribute, mixed $value, Closure $fail): void
    {
        $institutionUser = $this->findInstitutionUserWithAnyStatus($value);

        if ($institutionUser->getStatus() !== InstitutionUserStatus::Deactivated) {
            $fail('Institution user’s status is not deactivated.');
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function institutionUserIdIsValid(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->validated('institution_user_id'))) {
            $fail('Can’t validate roles since institution user id not valid.');
        }
    }

    private function existsRoleInSameInstitution(): ModelBelongsToInstitutionRule
    {
        return new ModelBelongsToInstitutionRule(
            Role::class,
            fn () => $this->findInstitutionUser()->institution_id
        );
    }

    private function findInstitutionUser(): InstitutionUser
    {
        return $this->findInstitutionUserWithAnyStatus($this->validated('institution_user_id'));
    }
}
