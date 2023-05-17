<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Feature\Models\Database;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstitutionUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $createdModel = InstitutionUser::factory()->create();
        $this->assertModelExists($createdModel);
    }

    public function test_institution_exists(): void
    {
        $expectedInstitutionName = 'Eesti Keele Instituut';

        $createdRole = InstitutionUser::factory()->forInstitution(['name' => $expectedInstitutionName])->create();
        $this->assertModelExists($createdRole->institution);

        $retrievedInstitution = Institution::findOrFail($createdRole->institution->id);
        $this->assertEquals($expectedInstitutionName, $retrievedInstitution->name);
    }

    public function test_user_exists(): void
    {
        $expectedPic = '47607239590';

        $createdInstitutionUser = InstitutionUser::factory()->forUser(['personal_identification_code' => $expectedPic])->create();
        $this->assertModelExists($createdInstitutionUser->user);

        $retrievedUser = User::findOrFail($createdInstitutionUser->user->id);
        $this->assertEquals($expectedPic, $retrievedUser->personal_identification_code);
    }

    public function test_duplicate_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $referenceInstitution = Institution::factory()->create();
        $referenceUser = User::factory()->create();

        InstitutionUser::factory()
            ->count(2)
            ->for($referenceUser)
            ->for($referenceInstitution)
            ->create();
    }

    public function test_status_constraint(): void
    {
        $referenceInstitution = Institution::factory()->create();
        $referenceUser = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('institution_users')->insert([
            'id' => Str::uuid(),
            'institution_id' => $referenceInstitution->id,
            'user_id' => $referenceUser->id,
            'status' => '!!!',
        ]);
    }

    public function test_adding_role_pivots(): void
    {
        $institution = Institution::factory()->create();
        $firstRole = Role::factory()->for($institution)->create();
        $secondRole = Role::factory()->for($institution)->create();
        $institutionUser = InstitutionUser::factory()->for($institution)->create();

        $institutionUser->roles()->sync([$firstRole->id, $secondRole->id]);

        $actualRoleIds = $institutionUser->refresh()->roles->pluck('id')->toArray();
        $this->assertCount(2, $actualRoleIds);
        $this->assertContains($firstRole->id, $actualRoleIds);
        $this->assertContains($secondRole->id, $actualRoleIds);
    }

    public function test_detaching_role_pivots(): void
    {
        $institution = Institution::factory()->create();
        $firstRole = Role::factory()->for($institution)->create();
        $secondRole = Role::factory()->for($institution)->create();
        $institutionUser = InstitutionUser::factory()
            ->for($institution)
            ->hasAttached($firstRole)
            ->hasAttached($secondRole)
            ->create();

        $firstRolePivot = InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $firstRole->id,
        ]);
        $secondRolePivot = InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $secondRole->id,
        ]);

        $this->assertModelExists($firstRolePivot);
        $this->assertModelExists($secondRolePivot);

        $institutionUser->roles()->sync([]);

        $this->assertEmpty($institutionUser->refresh()->roles);
        $this->assertEmpty(InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $firstRole->id,
        ]));
        $this->assertEmpty(InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $secondRole->id,
        ]));
    }

    public function test_soft_deleting_role_pivots(): void
    {
        $institution = Institution::factory()->create();
        $firstRole = Role::factory()->for($institution)->create();
        $secondRole = Role::factory()->for($institution)->create();
        $institutionUser = InstitutionUser::factory()
            ->for($institution)
            ->hasAttached($firstRole)
            ->hasAttached($secondRole)
            ->create();

        $firstRolePivot = InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $firstRole->id,
        ]);
        $secondRolePivot = InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $secondRole->id,
        ]);

        $firstRolePivot->deleteOrFail();
        $secondRolePivot->deleteOrFail();

        $this->assertEmpty($institutionUser->refresh()->roles);
        $this->assertSoftDeleted($firstRolePivot);
        $this->assertSoftDeleted($secondRolePivot);
    }
}
