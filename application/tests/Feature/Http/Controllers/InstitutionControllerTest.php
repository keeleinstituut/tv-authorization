<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;

class InstitutionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(Carbon::now());
    }

    public function test_correct_data_is_returned(): void
    {
        // GIVEN there’s a user connected to two institutions in the database
        InstitutionUser::factory()
            ->for($expectedInstitution1 = Institution::factory()->create())
            ->for($user = User::factory()->create())
            ->create();
        InstitutionUser::factory()
            ->for($expectedInstitution2 = Institution::factory()->create())
            ->for($user)
            ->create();

        // WHEN request sent to endpoint
        $response = $this->sendGetRequestWithTokenForGivenUser($user);

        // THEN request response should only contain the institutions expected
        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [
                    RepresentationHelpers::createInstitutionFlatRepresentation($expectedInstitution1),
                    RepresentationHelpers::createInstitutionFlatRepresentation($expectedInstitution2),
                ],
            ]);
    }

    public function test_soft_deleted_institution_is_excluded(): void
    {
        // GIVEN there’s a user connected to two institutions in the database, one of which has been soft deleted
        InstitutionUser::factory()
            ->for($expectedInstitution = Institution::factory()->create())
            ->for($user = User::factory()->create())
            ->create();
        InstitutionUser::factory()
            ->for($softDeletedInstitution = Institution::factory()->create())
            ->for($user)
            ->create();

        $softDeletedInstitution->delete();

        // WHEN request sent to endpoint
        $response = $this->sendGetRequestWithTokenForGivenUser($user);

        // THEN request response should only contain the institution that wasn't soft deleted
        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [RepresentationHelpers::createInstitutionFlatRepresentation($expectedInstitution)],
            ]);
    }

    public function test_soft_deleted_institution_user_is_excluded(): void
    {
        // GIVEN there’s a user connected to two institutions in the database, with one for the pivots having been soft deleted
        InstitutionUser::factory()
            ->for($expectedInstitution = Institution::factory()->create())
            ->for($user = User::factory()->create())
            ->create();
        InstitutionUser::factory()
            ->for(Institution::factory())
            ->for($user)
            ->create()
            ->delete();

        // WHEN request sent to endpoint
        $response = $this->sendGetRequestWithTokenForGivenUser($user);

        // THEN request response should only contain the institution for which the pivot wasn't soft deleted
        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [RepresentationHelpers::createInstitutionFlatRepresentation($expectedInstitution)],
            ]);
    }

    public function test_soft_deleted_user_is_excluded(): void
    {
        // GIVEN there’s a user connected to an institution in the database, but the user has been soft deleted
        InstitutionUser::factory()
            ->for(Institution::factory())
            ->for($user = User::factory()->create())
            ->create();
        $user->delete();

        // WHEN request sent to endpoint
        $response = $this->sendGetRequestWithTokenForGivenUser($user);

        // THEN response data should be empty
        $response
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_requesting_nonexistent_user(): void
    {
        // GIVEN the following data is in database
        InstitutionUser::factory()
            ->for(Institution::factory())
            ->for(User::factory()->create([
                'personal_identification_code' => '60810202280',
            ]))->create();

        // WHEN request targets nonexistent institution user
        $response = $this->sendGetRequestWithGivenToken(AuthHelpers::generateAccessToken([
            'personalIdentificationCode' => '32402126598',
            'userId' => Str::orderedUuid(),
            'forename' => fake()->firstName(),
            'surname' => fake()->lastName(),
        ]));

        // THEN response data should be empty
        $response
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_updating_user_without_access_token(): void
    {
        // GIVEN the following data is in database
        InstitutionUser::factory()
            ->for(Institution::factory())
            ->for(User::factory())
            ->create();

        // WHEN request sent without access token in header
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/institutions');

        // THEN response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    public function test_updating_user_when_tolkevarav_claims_empty(): void
    {
        // GIVEN the following data is in database
        InstitutionUser::factory()
            ->for(Institution::factory())
            ->for(User::factory())
            ->create();

        // WHEN request sent with Tolkevarav claims empty
        $response = $this->sendGetRequestWithGivenToken(AuthHelpers::generateAccessToken());

        // THEN response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    private function sendGetRequestWithTokenForGivenUser(User $user): TestResponse
    {
        $accessToken = AuthHelpers::generateAccessToken(AuthHelpers::makeTolkevaravClaimsForUser($user));

        return $this->sendGetRequestWithGivenToken($accessToken);
    }

    private function sendGetRequestWithGivenToken(string $accessToken): TestResponse
    {
        return $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->getJson('/api/institutions');
    }
}
