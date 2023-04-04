<?php

namespace Feature\Routes;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class JwtClaimsTest extends TestCase
{
    use RefreshDatabase;

    public function test_random_uuid_returns_404(): void
    {
        $this->get('/api/jwt-claims/'.Str::uuid())->assertStatus(404);
    }

    public function test_correct_claims_returned_for_user_with_no_institution(): void
    {
        [
            'users' => [$userWithNoInstitution]
        ] = $this->createInstutionUsers();

        $this->get('/api/jwt-claims/'.$userWithNoInstitution->id)
            ->assertStatus(200)
            ->assertExactJson([
                'personalIdentityCode' => $userWithNoInstitution->personal_identification_code,
                'forename' => $userWithNoInstitution->forename,
                'surname' => $userWithNoInstitution->surname,
                'institutions' => [],
            ]);
    }

    public function test_correct_claims_returned_for_user_with_single_institution(): void
    {
        [
            'users' => [, $userWithSingleInstitution],
            'institutions' => [$firstInstitution]
        ] = $this->createInstutionUsers();

        $this->get('/api/jwt-claims/'.$userWithSingleInstitution->id)
            ->assertStatus(200)
            ->assertExactJson([
                'personalIdentityCode' => $userWithSingleInstitution->personal_identification_code,
                'forename' => $userWithSingleInstitution->forename,
                'surname' => $userWithSingleInstitution->surname,
                'institutions' => [$firstInstitution->id],
            ]);
    }

    public function test_correct_claims_returned_for_user_with_two_institutions(): void
    {
        [
            'users' => [, , $userWithTwoInstitutions],
            'institutions' => [$firstInstitution, $secondInstitution]
        ] = $this->createInstutionUsers();

        $this->get('/api/jwt-claims/'.$userWithTwoInstitutions->id)
            ->assertStatus(200)
            ->assertExactJson([
                'personalIdentityCode' => $userWithTwoInstitutions->personal_identification_code,
                'forename' => $userWithTwoInstitutions->forename,
                'surname' => $userWithTwoInstitutions->surname,
                'institutions' => [$firstInstitution->id, $secondInstitution->id],
            ]);
    }

    private function createInstutionUsers(): array
    {
        [$userWithNoInstitution, $userWithSingleInstitution, $userWithTwoInstitutions] = User::factory()->count(3)->create();
        [$firstInstitution, $secondInstitution] = Institution::factory()->count(3)->create();

        InstitutionUser::factory()
            ->for($userWithSingleInstitution)
            ->for($firstInstitution)
            ->create();

        InstitutionUser::factory()
            ->for($userWithTwoInstitutions)
            ->for($firstInstitution)
            ->create();

        InstitutionUser::factory()
            ->for($userWithTwoInstitutions)
            ->for($secondInstitution)
            ->create();

        return [
            'users' => [$userWithNoInstitution, $userWithSingleInstitution, $userWithTwoInstitutions],
            'institutions' => [$firstInstitution, $secondInstitution],
        ];
    }
}
