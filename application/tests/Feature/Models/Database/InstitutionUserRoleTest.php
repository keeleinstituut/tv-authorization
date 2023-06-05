<?php

namespace Tests\Feature\Models\Database;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $createdModel = InstitutionUserRole::factory()->create();
        $this->assertModelExists($createdModel);
    }

    public function test_referenced_rows_exist(): void
    {
        $expectedInstitutionName = 'Eesti Keele Instituut';
        $expectedRoleName = 'John Smith';
        $expectedPIC = '47607239590';

        $referenceInstitution = Institution::factory()->create(['name' => $expectedInstitutionName]);
        $referenceUser = User::factory()
            ->create(['personal_identification_code' => $expectedPIC]);
        $referenceRole = Role::factory()
            ->for($referenceInstitution)
            ->create(['name' => $expectedRoleName]);
        $referenceInstitutionUser = InstitutionUser::factory()
            ->for($referenceInstitution)
            ->for($referenceUser)
            ->create();

        $createdInstitutionUserRole = InstitutionUserRole::factory()
            ->for($referenceInstitutionUser)
            ->for($referenceRole)
            ->create();

        $this->assertModelExists($createdInstitutionUserRole->institutionUser);
        $this->assertModelExists($createdInstitutionUserRole->role);
        $this->assertModelExists($createdInstitutionUserRole->institutionUser->user);
        $this->assertModelExists($createdInstitutionUserRole->institutionUser->institution);
        $this->assertModelExists($createdInstitutionUserRole->role->institution);

        $this->assertEquals($referenceInstitution->id, $createdInstitutionUserRole->institutionUser->institution->id);
        $this->assertEquals($referenceInstitution->id, $createdInstitutionUserRole->role->institution->id);

        $retrievedInstitution = Institution::findOrFail($createdInstitutionUserRole->role->institution->id);
        $this->assertEquals($expectedInstitutionName, $retrievedInstitution->name);

        $retrievedRole = Role::findOrFail($createdInstitutionUserRole->role->id);
        $this->assertEquals($expectedRoleName, $retrievedRole->name);

        $retrievedUser = User::findOrFail($createdInstitutionUserRole->institutionUser->user->id);
        $this->assertModelExists($retrievedUser);
        $this->assertEquals($expectedPIC, $retrievedUser->personal_identification_code);
    }

    public function test_duplicate_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $referenceInstitutionUser = InstitutionUser::factory()->create();
        $referenceRole = Role::factory()->create();

        InstitutionUserRole::factory()
            ->count(2)
            ->for($referenceRole)
            ->for($referenceInstitutionUser)
            ->create();
    }
}
