<?php

namespace Tests\Feature\Models\Database;

use App\Enums\PrivilegeKey;
use App\Exceptions\DeniedRootRoleModifyException;
use App\Models\Institution;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $expectedRoleName = 'Translation Manager';

        $createdModel = Role::factory()->create(['name' => $expectedRoleName]);
        $this->assertModelExists($createdModel);

        $retrievedModel = Role::findOrFail($createdModel->id);
        $this->assertEquals($expectedRoleName, $retrievedModel->name);
    }

    public function test_institution_name_unique_constraint(): void
    {
        $institution = Institution::factory()->create();
        $roleName = 'Translation Manager';

        Role::factory()->for($institution)->create(['name' => $roleName]);

        $this->expectException(QueryException::class);
        Role::factory()->for($institution)->create(['name' => $roleName]);
    }

    public function test_institution_exists(): void
    {
        $expectedInstitutionName = 'Eesti Keele Instituut';

        $createdRole = Role::factory()->forInstitution(['name' => $expectedInstitutionName])->create();
        $this->assertModelExists($createdRole->institution);

        $retrievedInstitution = Institution::findOrFail($createdRole->institution->id);
        $this->assertEquals($expectedInstitutionName, $retrievedInstitution->name);
    }

    public function test_adding_privilege_pivots(): void
    {
        $role = Role::factory()->for(Institution::factory()->create())->create();
        $addUserPrivilege = Privilege::firstWhere('key', PrivilegeKey::AddUser->value);
        $viewUserPrivilege = Privilege::firstWhere('key', PrivilegeKey::ViewUser->value);

        $role->privileges()->sync([$addUserPrivilege->id, $viewUserPrivilege->id]);

        $actualPrivilegeIds = $role->refresh()->privileges->pluck('id')->toArray();
        $this->assertEquals(2, PrivilegeRole::whereRoleId($role->id)->count());
        $this->assertCount(2, $actualPrivilegeIds);
        $this->assertContains($addUserPrivilege->id, $actualPrivilegeIds);
        $this->assertContains($viewUserPrivilege->id, $actualPrivilegeIds);
    }

    public function test_detaching_privilege_pivots(): void
    {
        $addUserPrivilege = Privilege::firstWhere('key', PrivilegeKey::AddUser->value);
        $viewUserPrivilege = Privilege::firstWhere('key', PrivilegeKey::ViewUser->value);
        $role = Role::factory()
            ->for(Institution::factory()->create())
            ->hasAttached([$addUserPrivilege, $viewUserPrivilege])
            ->create();

        $addUserPivot = PrivilegeRole::firstWhere([
            'privilege_id' => $addUserPrivilege->id,
            'role_id' => $role->id,
        ]);
        $viewUserPivot = PrivilegeRole::firstWhere([
            'privilege_id' => $viewUserPrivilege->id,
            'role_id' => $role->id,
        ]);

        $this->assertModelExists($addUserPivot);
        $this->assertModelExists($viewUserPivot);

        $role->privileges()->sync([]);

        $this->assertEmpty($role->refresh()->privileges);
        $this->assertEmpty(PrivilegeRole::firstWhere([
            'privilege_id' => $addUserPrivilege->id,
            'role_id' => $role->id,
        ]));
        $this->assertEmpty(PrivilegeRole::firstWhere([
            'privilege_id' => $viewUserPrivilege->id,
            'role_id' => $role->id,
        ]));
    }

    public function test_should_fail_is_root_role_delete(): void
    {
        // GIVEN
        $testRole = Role::factory()->make();
        $testRole->is_root = true;
        $testRole->save();

        // THEN
        $this->expectException(DeniedRootRoleModifyException::class);

        // WHEN
        $testRole->delete();
    }
}
