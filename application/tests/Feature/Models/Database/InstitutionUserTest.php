<?php

namespace Feature\Models\Database;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $expectedInstitutionName = 'Eesti Keele Instituut';

        $createdRole = InstitutionUser::factory()->forInstitution(['name' => $expectedInstitutionName])->create();
        $this->assertModelExists($createdRole->institution);

        $retrievedInstitution = Institution::findOrFail($createdRole->institution->id);
        $this->assertEquals($expectedInstitutionName, $retrievedInstitution->name);
    }

    public function test_user_exists(): void
    {
        $expectedPic = '47607239590';

        $createdInstitutionUser = InstitutionUser::factory()->forUser(['personal_identification_code' => $expectedPic])->create();
        $this->assertModelExists($createdInstitutionUser->user);

        $retrievedUser = User::findOrFail($createdInstitutionUser->user->id);
        $this->assertEquals($expectedPic, $retrievedUser->personal_identification_code);
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

    public function test_status_constraint(): void
    {
        $referenceInstitution = Institution::factory()->create();
        $referenceUser = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('institution_users')->insert([
            'id' => Str::uuid(),
            'institution_id' => $referenceInstitution->id,
            'user_id' => $referenceUser->id,
            'status' => '!!!',
        ]);
    }
}
