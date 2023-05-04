<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Feature\Models\Database;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class PrivilegeTest extends TestCase
{
    use RefreshDatabase;

    public function test_privilege_table_is_equal_to_enum(): void
    {
        $keysInDatabase = Privilege::all()
            ->map(fn ($model) => $model->key->value)
            ->toArray();

        $keysInEnum = Arr::map(
            PrivilegeKey::cases(),
            fn ($enum) => $enum->value
        );

        $this->assertEmpty(array_diff($keysInDatabase, $keysInEnum)); // Check no keys missing in enum
        $this->assertEmpty(array_diff($keysInEnum, $keysInDatabase)); // Check no keys missing in database
    }

    public function test_adding_role_pivots(): void
    {
        $firstRole = Role::factory()->for(Institution::factory()->create())->create();
        $secondRole = Role::factory()->for(Institution::factory()->create())->create();
        $privilege = Privilege::firstWhere('key', PrivilegeKey::AddUser->value);

        $privilege->roles()->sync([$firstRole->id, $secondRole->id]);

        $actualRoleIds = $privilege->refresh()->roles->pluck('id')->toArray();
        $this->assertContains($firstRole->id, $actualRoleIds);
        $this->assertContains($secondRole->id, $actualRoleIds);
    }

    public function test_detaching_role_pivots(): void
    {
        $privilege = Privilege::firstWhere('key', PrivilegeKey::AddUser->value);
        $firstRole = Role::factory()->for(Institution::factory()->create())->hasAttached($privilege)->create();
        $secondRole = Role::factory()->for(Institution::factory()->create())->hasAttached($privilege)->create();

        $firstRolePivot = PrivilegeRole::firstWhere([
            'privilege_id' => $privilege->id,
            'role_id' => $firstRole->id,
        ]);
        $secondRolePivot = PrivilegeRole::firstWhere([
            'privilege_id' => $privilege->id,
            'role_id' => $secondRole->id,
        ]);

        $this->assertModelExists($firstRolePivot);
        $this->assertModelExists($secondRolePivot);

        $privilege->roles()->sync([]);

        $this->assertEmpty($privilege->refresh()->roles);
        $this->assertEmpty(PrivilegeRole::firstWhere([
            'privilege_id' => $privilege->id,
            'role_id' => $firstRole->id,
        ]));
        $this->assertEmpty(PrivilegeRole::firstWhere([
            'privilege_id' => $privilege->id,
            'role_id' => $secondRole->id,
        ]));
    }

    public function test_soft_deleting_role_pivots(): void
    {
        $privilege = Privilege::firstWhere('key', PrivilegeKey::AddUser->value);
        $firstRole = Role::factory()->for(Institution::factory()->create())->hasAttached($privilege)->create();
        $secondRole = Role::factory()->for(Institution::factory()->create())->hasAttached($privilege)->create();

        $firstRolePivot = PrivilegeRole::firstWhere([
            'privilege_id' => $privilege->id,
            'role_id' => $firstRole->id,
        ]);
        $secondRolePivot = PrivilegeRole::firstWhere([
            'privilege_id' => $privilege->id,
            'role_id' => $secondRole->id,
        ]);

        $countOfRolesBeforeDeletion = $privilege->roles->count(); // can’t assume db is empty

        $firstRolePivot->deleteOrFail();
        $secondRolePivot->deleteOrFail();

        $this->assertCount($countOfRolesBeforeDeletion - 2, $privilege->refresh()->roles);
        $this->assertSoftDeleted($firstRolePivot);
        $this->assertSoftDeleted($secondRolePivot);
    }
}
