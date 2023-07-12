<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;

class RepresentationHelpers
{
    public static function createInstitutionUserNestedRepresentation(InstitutionUser $institutionUser): array
    {
        return [
            ...self::createInstitutionUserFlatRepresentation($institutionUser),
            'user' => self::createUserFlatRepresentation($institutionUser->user),
            'institution' => self::createInstitutionFlatRepresentation($institutionUser->institution),
            'department' => empty($institutionUser->department)
                ? null
                : self::createDepartmentFlatRepresentation($institutionUser->department),
            'roles' => $institutionUser->roles
                ->map(self::createRoleNestedRepresentation(...))
                ->all(),
        ];
    }

    public static function createRoleNestedRepresentation(Role $role): array
    {
        return [
            ...Arr::only(
                $role->toArray(),
                ['id', 'name', 'institution_id', 'created_at', 'updated_at', 'is_root']
            ),
            'privileges' => $role->privileges
                ->map(fn (Privilege $privilege) => ['key' => $privilege->key->value])
                ->toArray(),
        ];
    }

    public static function createDepartmentFlatRepresentation(Department $department): array
    {
        return Arr::only(
            $department->toArray(),
            ['id', 'institution_id', 'name', 'created_at', 'updated_at']
        );
    }

    public static function createUserFlatRepresentation(?User $user): array
    {
        return Arr::only(
            $user?->toArray() ?? [],
            ['id', 'personal_identification_code', 'forename', 'surname', 'updated_at', 'created_at']
        );
    }

    public static function createInstitutionUserFlatRepresentation(InstitutionUser $institutionUser): array
    {
        return [
            ...Arr::only(
                $institutionUser->toArray(),
                ['id', 'email', 'phone', 'updated_at', 'created_at', 'archived_at', 'deactivation_date']
            ),
            'status' => $institutionUser->getStatus()->value,
        ];
    }

    public static function createInstitutionFlatRepresentation(Institution $institution): array
    {
        return Arr::only($institution->toArray(), [
            'id',
            'name',
            'logo_url',
            'updated_at',
            'created_at',
            'short_name',
            'phone',
            'email',
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
        ]);
    }
}
