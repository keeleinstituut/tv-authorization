<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateInstitutionRoleAction;
use App\DataTransferObjects\InstitutionRoleData;
use App\Enums\PrivilegeKey;
use App\Exceptions\EmptyRoleNameException;
use App\Exceptions\EmptyRolePrivilegesException;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

class CreateInstitutionRoleActionTest extends TestCase
{
    use RefreshDatabase;

    /** * @throws Throwable */
    public function test_creation_of_institution_role(): void
    {
        $institution = Institution::factory()->createOne();

        $role = (new CreateInstitutionRoleAction)->execute(
            new InstitutionRoleData(
                'some role name',
                $institution->id,
                PrivilegeKey::cases()
            )
        );

        $this->assertModelExists($role);
        $this->assertEquals($institution->id, $role->institution_id);
        $this->assertCount(count(PrivilegeKey::cases()), $role->privilegeRoles);
    }

    /** * @throws Throwable */
    public function test_creation_of_institution_role_without_privileges(): void
    {
        $institution = Institution::factory()->createOne();
        $this->expectException(EmptyRolePrivilegesException::class);

        (new CreateInstitutionRoleAction)->execute(
            new InstitutionRoleData(
                'some role name',
                $institution->id,
                []
            )
        );
    }

    /** * @throws Throwable */
    public function test_creation_of_institution_role_with_empty_name(): void
    {
        $institution = Institution::factory()->createOne();
        $this->expectException(EmptyRoleNameException::class);

        (new CreateInstitutionRoleAction)->execute(
            new InstitutionRoleData(
                '',
                $institution->id,
                PrivilegeKey::cases()
            )
        );
    }

    public function test_creation_of_institution_role_with_incorrect_institution_id(): void
    {
        $this->expectException(Throwable::class);
        (new CreateInstitutionRoleAction)->execute(
            new InstitutionRoleData(
                'some role',
                Str::orderedUuid(),
                PrivilegeKey::cases()
            )
        );
    }
}
