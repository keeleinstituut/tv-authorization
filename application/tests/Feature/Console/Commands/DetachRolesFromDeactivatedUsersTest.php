<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\DetachRolesFromDeactivatedUsers;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tests\Feature\InstitutionUserHelpers;
use Tests\TestCase;

class DetachRolesFromDeactivatedUsersTest extends TestCase
{
    use RefreshDatabase, InstitutionUserHelpers;

    public function test_deactivated_institution_users_have_no_roles_and_pivots_are_soft_deleted(): void
    {
        // GIVEN the current Estonian time is 2000-01-01T00:00:00
        Date::setTestNow(Date::create(2000, tz: 'Europe/Tallinn'));

        // And there are deactivated as well as active institution users in database, all having at least one role
        $deactivatedInstitutionUsers = InstitutionUser::factory()
            ->count(3)
            ->for(Institution::factory())
            ->has(User::factory())
            ->has(Role::factory()->count(3))
            ->create(['deactivation_date' => '2000-01-01']);
        $notYetDeactivatedInstitutionUsers = InstitutionUser::factory()
            ->count(3)
            ->for(Institution::factory())
            ->has(User::factory())
            ->has(Role::factory()->count(3))
            ->create(['deactivation_date' => '2000-01-02']);
        $activeInstitutionUsers = InstitutionUser::factory()
            ->count(3)
            ->for(Institution::factory())
            ->has(User::factory())
            ->has(Role::factory()->count(3))
            ->create(['deactivation_date' => null]);

        $roleIds = collect([$deactivatedInstitutionUsers, $notYetDeactivatedInstitutionUsers, $activeInstitutionUsers])
            ->flatten()
            ->flatMap(fn (InstitutionUser $institutionUser) => $institutionUser->roles)
            ->map(fn (Role $role) => $role->id);
        $pivotIdsExpectedToSoftDelete = $deactivatedInstitutionUsers
            ->flatMap(fn (InstitutionUser $institutionUser) => $institutionUser->institutionUserRoles)
            ->map(fn (InstitutionUserRole $institutionUserRole) => $institutionUserRole->id);
        $pivotIdsExpectedToRemain = collect([$notYetDeactivatedInstitutionUsers, $activeInstitutionUsers])
            ->flatten()
            ->flatMap(fn (InstitutionUser $institutionUser) => $institutionUser->institutionUserRoles)
            ->map(fn (InstitutionUserRole $institutionUserRole) => $institutionUserRole->id);

        // And given (sanity check) every created institution user has at least one role
        collect([$deactivatedInstitutionUsers, $notYetDeactivatedInstitutionUsers, $activeInstitutionUsers])
            ->flatten()
            ->map(fn (InstitutionUser $institutionUser) => $institutionUser->roles->count())
            ->each(fn ($count) => $this->assertGreaterThan(0, $count));

        // WHEN command to detach roles from deactivated users is called
        $exitCode = Artisan::call(DetachRolesFromDeactivatedUsers::class);

        // THEN command exit code should indicate success
        $this->assertSame(CommandAlias::SUCCESS, $exitCode);

        // And expected pivot rows should be soft deleted
        $pivotIdsExpectedToSoftDelete->each(
            fn ($id) => $this->assertSoftDeleted(InstitutionUserRole::class, ['id' => $id])
        );

        // And deactivated users should now have zero roles
        $deactivatedInstitutionUsers
            ->map(fn (InstitutionUser $institutionUser) => $institutionUser->refresh()->roles->count())
            ->each(fn ($count) => $this->assertSame(0, $count));

        // But non-deactivated users should still have at least one role
        collect([$notYetDeactivatedInstitutionUsers, $activeInstitutionUsers])
            ->flatten()
            ->map(fn (InstitutionUser $institutionUser) => $institutionUser->refresh()->roles->count())
            ->each(fn ($count) => $this->assertGreaterThan(0, $count));

        // And pivots of non-deactivated users should still exist
        $pivotIdsExpectedToRemain
            ->map(fn ($id) => InstitutionUserRole::findOrFail($id))
            ->map(fn (InstitutionUserRole $pivot) => $pivot->exists())
            ->each($this->assertTrue(...));

        // And all roles should still exist
        $roleIds
            ->map(fn ($id) => Role::findOrFail($id))
            ->map(fn (Role $role) => $role->exists())
            ->each($this->assertTrue(...));

        // And all users should still exist
        collect([$deactivatedInstitutionUsers, $notYetDeactivatedInstitutionUsers, $activeInstitutionUsers])
            ->flatten()
            ->map(fn (InstitutionUser $institutionUser) => $institutionUser->refresh()->exists())
            ->each($this->assertTrue(...));
    }
}
