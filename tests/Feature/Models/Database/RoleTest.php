<?php

namespace Feature\Models\Database;

use App\Models\Institution;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $expected_name = 'role-test_test-saving';

        $createdModel = Role::factory()->create(['name' => $expected_name]);
        $this->assertModelExists($createdModel);

        $retrievedModel = Role::findOrFail($createdModel->id);
        $this->assertEquals($expected_name, $retrievedModel->name);
    }

    public function test_institution_exists(): void
    {
        $expected_name = 'role-test_test-institution-exists';

        $createdRole = Role::factory()->forInstitution(['name' => $expected_name])->create();
        $this->assertModelExists($createdRole->institution);

        $retrievedInstitution = Institution::findOrFail($createdRole->institution->id);
        $this->assertEquals($expected_name, $retrievedInstitution->name);
    }
}
