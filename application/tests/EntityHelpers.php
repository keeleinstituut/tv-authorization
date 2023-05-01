<?php

namespace Tests;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;

trait EntityHelpers
{
    private function createInstitution(): Institution
    {
        return Institution::factory()->create();
    }

    /**
     * @param  array<PrivilegeKey>  $privileges
     */
    private function createRoleWithPrivileges(Institution $institution, array $privileges): Role
    {
        $role = Role::factory()->for($institution)->create();

        foreach (collect($privileges)->unique() as $privilegeKey) {
            $privilege = Privilege::where('key', '=', $privilegeKey->value)->firstOrFail();
            PrivilegeRole::factory()->for($role)->for($privilege)->create();
        }

        return $role;
    }

    private function createInstitutionUserWithRoles(Institution $institution, Role ...$roles): InstitutionUser
    {
        return InstitutionUser::factory()
            ->for($institution)
            ->has(InstitutionUserRole::factory()->forEachSequence(
                ...collect($roles)->map(fn (Role $role) => ['role_id' => $role->id])
            ))
            ->create();
    }
}
