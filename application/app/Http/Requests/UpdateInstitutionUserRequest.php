<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Helpers\WorktimeValidationUtil;
use App\Http\Requests\Helpers\MaxLengthValue;
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
        nullable: false,
        anyOf: [
            new OA\Schema(
                required: [
                    'name',
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
                ],
                type: 'object'
            ),
        ],
    )
)]
class UpdateInstitutionUserRequest extends FormRequest
{
    private ?InstitutionUser $targetInstitutionUser = null;

    public function rules(): array
    {
        return [
            'user' => ['array', 'min:1'],
            'user.forename' => ['filled', 'max:' . MaxLengthValue::USERNAME_PART],
            'user.surname' => ['filled', 'max:' . MaxLengthValue::USERNAME_PART],
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
            ...WorktimeValidationUtil::buildWorktimeValidationRules(),
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

    public function hasAnyWorktimeInput(): bool
    {
        return filled($this->getValidatedWorktimeInput());
    }

    public function hasAnyNonCalendarInput(): bool
    {
        return filled($this->getValidatedNonCalendarInput());
    }

    public function getValidatedWorktimeInput(): array
    {
        $worktimeAttributeKeys = WorktimeValidationUtil::getWorktimeIntervalEdgesByDay()
            ->flatten()
            ->push('worktime_timezone')
            ->all();

        return $this->safe($worktimeAttributeKeys);
    }

    public function getValidatedNonCalendarInput(): array
    {
        return $this->safe(['user', 'email', 'phone', 'roles', 'department_id']);
    }

    public function after(): array
    {
        return [
            WorktimeValidationUtil::validateAllWorktimeFieldsArePresentOrAllMissing(...),
            WorktimeValidationUtil::validateEachWorktimeStartIsBeforeEndOrBothUndefined(...),
        ];
    }
}
