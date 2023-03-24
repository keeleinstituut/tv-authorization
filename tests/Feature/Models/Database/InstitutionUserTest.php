<?php

namespace Feature\Models\Database;

use App\Enum\InstitutionUserStatusKey;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserStatus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $expectedName = 'institution-user-test_test-institution-exists';

        $createdRole = InstitutionUser::factory()->forInstitution(['name' => $expectedName])->create();
        $this->assertModelExists($createdRole->institution);

        $retrievedInstitution = Institution::findOrFail($createdRole->institution->id);
        $this->assertEquals($expectedName, $retrievedInstitution->name);
    }

    public function test_user_exists(): void
    {
        $expectedPic = '39611300828';

        $createdInstitutionUser = InstitutionUser::factory()->forUser(['personal_identification_code' => $expectedPic])->create();
        $this->assertModelExists($createdInstitutionUser->user);

        $retrievedUser = User::findOrFail($createdInstitutionUser->user->id);
        $this->assertEquals($expectedPic, $retrievedUser->personal_identification_code);
    }

    public function test_status_exists(): void
    {
        $expectedKey = InstitutionUserStatusKey::Created->value;
        $referenceStatus = InstitutionUserStatus::where('key', $expectedKey)->firstOrFail();

        $createdInstitutionUser = InstitutionUser::factory()->for($referenceStatus)->create();
        $this->assertModelExists($createdInstitutionUser->institutionUserStatus);

        $retrievedStatus = InstitutionUserStatus::findOrFail($createdInstitutionUser->institutionUserStatus->id);
        $this->assertEquals($expectedKey, $retrievedStatus->key->value);
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
}
