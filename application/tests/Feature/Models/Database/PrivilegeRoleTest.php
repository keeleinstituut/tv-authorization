<?php

namespace Tests\Feature\Models\Database;

use App\Enums\PrivilegeKey;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}
