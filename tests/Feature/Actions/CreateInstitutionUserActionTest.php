<?php

namespace Feature\Actions;

use App\Actions\CreateInstitutionUserAction;
use App\DataTransferObjects\UserData;
use App\Exceptions\EmptyUserRolesException;
use App\Models\Institution;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateInstitutionUserActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_of_institution_user(): void
    {
        $institution = Institution::factory()->createOne();
        $role = Role::factory()->createOne();
        $institutionUser = (new CreateInstitutionUserAction)->execute(
            new UserData(
                '46304255763',
                'some@email.com',
                'some surname',
                'some forename'
            ),
            $institution->id,
            [$role->id]
        );

        $this->assertModelExists($institutionUser);
        $this->assertModelExists($institutionUser->user);
        $this->assertEquals($institution->id, $institutionUser->institution_id);
        $this->assertEquals(
            [$role->id],
            $institutionUser->institutionUserRoles
                ->map(fn (InstitutionUserRole $role) => $role->role_id)
                ->toArray()
        );
    }

    public function test_creation_of_institution_user_without_roles(): void
    {
        $institution = Institution::factory()->createOne();
        $role = Role::factory()->createOne();

        $this->expectException(EmptyUserRolesException::class);
        (new CreateInstitutionUserAction)->execute(
            new UserData(
                '46304255763',
                'some@email.com',
                'some surname',
                'some forename'
            ),
            $institution->id,
            []
        );
    }
}