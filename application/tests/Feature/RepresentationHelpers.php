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
                ->toArray(),
        ];
    }

    public static function createRoleNestedRepresentation(Role $role): array
    {
        return [
            ...Arr::only(
                $role->toArray(),
                ['id', 'name', 'institution_id', 'created_at', 'updated_at']
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
                ['id', 'email', 'phone', 'updated_at', 'created_at', 'archived_at']
            ),
            'status' => $institutionUser->getStatus()->value,
            'deactivation_date' => $institutionUser->getDeactivationDateAsString(),
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
        ]);
    }
}
