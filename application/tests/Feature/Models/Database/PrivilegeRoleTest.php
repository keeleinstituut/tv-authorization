<?php

namespace Tests\Feature\Models\Database;

use App\Enums\PrivilegeKey;
use App\Exceptions\DeniedRootRoleModifyException;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function PHPUnit\Framework\assertEquals;

class PrivilegeRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $createdModel = PrivilegeRole::factory()->create();
        $this->assertModelExists($createdModel);
    }

    public function test_role_exists(): void
    {
        $expectedRoleName = 'Translation Manager';

        $createdPrivilegeRole = PrivilegeRole::factory()->forRole(['name' => $expectedRoleName])->create();
        $this->assertModelExists($createdPrivilegeRole->role);

        $retrievedRole = Role::findOrFail($createdPrivilegeRole->role->id);
        $this->assertEquals($expectedRoleName, $retrievedRole->name);
    }

    public function test_privilege_exists(): void
    {
        $expectedKey = PrivilegeKey::AddUser->value;
        $referencePrivilege = Privilege::where('key', $expectedKey)->firstOrFail();

        $createdPrivilegeRole = PrivilegeRole::factory()->for($referencePrivilege)->create();
        $this->assertModelExists($createdPrivilegeRole->privilege);

        $retrievedPrivilege = Privilege::findOrFail($createdPrivilegeRole->privilege->id);
        $this->assertEquals($expectedKey, $retrievedPrivilege->key->value);
    }

    public function test_duplicate_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $referencePrivilege = Privilege::where('key', PrivilegeKey::AddUser->value)->firstOrFail();
        $referenceRole = Role::factory()->create();

        PrivilegeRole::factory()
            ->count(2)
            ->for($referencePrivilege)
            ->for($referenceRole)
            ->create();
    }

    public function test_should_fail_deleting_when_is_root_role(): void {
        // GIVEN
        $testPrivilegeRole = PrivilegeRole::factory()->create();
        $testPrivilegeRole->role->is_root = true;
        $testPrivilegeRole->role->save();

        // THEN
        $this->expectException(DeniedRootRoleModifyException::class);

        // WHEN
        $testPrivilegeRole->delete();
    }

    public function test_should_fail_updating_privilege_id_when_is_root_role(): void {
        // GIVEN
        $testPrivilegeRole = PrivilegeRole::factory()->create();
        $testPrivilegeRole->role->is_root = true;
        $testPrivilegeRole->role->save();
        $testPrivilegeRoleSecond = PrivilegeRole::factory()->create();

        // THEN
        $this->expectException(DeniedRootRoleModifyException::class);

        // WHEN
        $testPrivilegeRole->update([
            'privilege_id' => $testPrivilegeRoleSecond->privilege_id
        ]);
    }

    public function test_should_fail_updating_role_id_when_is_root_role(): void {
        // GIVEN
        $testPrivilegeRole = PrivilegeRole::factory()->create();
        $testPrivilegeRole->role->is_root = true;
        $testPrivilegeRole->role->save();
        $testPrivilegeRoleSecond = PrivilegeRole::factory()->create();

        // THEN
        $this->expectException(DeniedRootRoleModifyException::class);

        // WHEN
        $testPrivilegeRole->update([
            'role_id' => $testPrivilegeRoleSecond->role_id
        ]);
    }

    public function test_should_fail_updating_role_id_and_privilege_id_when_is_root_role(): void {
        // GIVEN
        $testPrivilegeRole = PrivilegeRole::factory()->create();
        $testPrivilegeRole->role->is_root = true;
        $testPrivilegeRole->role->save();
        $testPrivilegeRoleSecond = PrivilegeRole::factory()->create();

        // THEN
        $this->expectException(DeniedRootRoleModifyException::class);

        // WHEN
        $testPrivilegeRole->update([
            'role_id' => $testPrivilegeRoleSecond->role_id,
            'privilege_id' => $testPrivilegeRoleSecond->privilege_id
        ]);
    }

    public function test_should_allow_updating_role_id_when_is_not_root_role(): void {
        // GIVEN
        $testPrivilegeRole = PrivilegeRole::factory()->create();
        $testPrivilegeRole->role->is_root = false;
        $testPrivilegeRole->role->save();
        $testPrivilegeRoleSecond = PrivilegeRole::factory()->create();

        // WHEN
        $testPrivilegeRole->update([
            'role_id' => $testPrivilegeRoleSecond->role_id,
        ]);

        // THEN
        assertEquals($testPrivilegeRoleSecond->role_id, $testPrivilegeRole->role_id);
    }

    public function test_should_allow_updating_privilege_id_when_is_not_root_role(): void {
        // GIVEN
        $testPrivilegeRole = PrivilegeRole::factory()->create();
        $testPrivilegeRole->role->is_root = false;
        $testPrivilegeRole->role->save();
        $testPrivilegeRoleSecond = PrivilegeRole::factory()->create();

        // WHEN
        $testPrivilegeRole->update([
            'privilege_id' => $testPrivilegeRoleSecond->privilege_id
        ]);

        // THEN
        assertEquals($testPrivilegeRoleSecond->privilege_id, $testPrivilegeRole->privilege_id);
    }
}
