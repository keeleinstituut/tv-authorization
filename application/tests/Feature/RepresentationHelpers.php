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
        // Load vacations relationships if not already loaded (matching controller behavior)
        if (! $institutionUser->relationLoaded('activeInstitutionUserVacations')) {
            $institutionUser->load('activeInstitutionUserVacations');
        }
        if (! $institutionUser->relationLoaded('activeInstitutionVacations')) {
            $institutionUser->load('activeInstitutionVacations');
        }
        if (! $institutionUser->relationLoaded('activeInstitutionVacationExclusions')) {
            $institutionUser->load('activeInstitutionVacationExclusions');
        }

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
            'vacations' => self::createInstitutionUserVacationsRepresentation($institutionUser),
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
                [
                    'id',
                    'email',
                    'phone',
                    'updated_at',
                    'created_at',
                    'archived_at',
                    'deactivation_date',
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
                ]
            ),
            'status' => $institutionUser->getStatus()->value,
        ];
    }

    public static function createInstitutionFlatRepresentation(Institution $institution): array
    {
        $representation = Arr::only($institution->toArray(), [
            'id',
            'name',
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

        // Match InstitutionResource behavior: logo_url is a route, not from database
        $representation['logo_url'] = route('authorization.institutions.logo', ['institution_id' => $institution->id]);

        return $representation;
    }

    public static function createInstitutionUserVacationsRepresentation(InstitutionUser $institutionUser): array
    {
        // Match InstitutionUserVacationsResource structure
        // The resource uses whenLoaded, which returns the relation if loaded, or empty collection if not
        $institutionUserVacations = $institutionUser->relationLoaded('activeInstitutionUserVacations')
            ? $institutionUser->activeInstitutionUserVacations
            : collect();

        $institutionVacations = $institutionUser->getActiveInstitutionVacationsWithExclusions();

        return [
            'institution_user_vacations' => $institutionUserVacations
                ->map(fn ($vacation) => Arr::only($vacation->toArray(), [
                    'id',
                    'institution_user_id',
                    'start_date',
                    'end_date',
                    'created_at',
                    'updated_at',
                ]))
                ->values()
                ->all(),
            'institution_vacations' => $institutionVacations
                ->map(fn ($vacation) => Arr::only($vacation->toArray(), [
                    'id',
                    'institution_id',
                    'start_date',
                    'end_date',
                    'created_at',
                    'updated_at',
                ]))
                ->values()
                ->all(),
        ];
    }
}
