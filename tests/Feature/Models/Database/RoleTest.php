<?php

namespace Feature\Models\Database;

use App\Models\Institution;
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
}
