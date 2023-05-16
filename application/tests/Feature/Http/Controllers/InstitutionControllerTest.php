<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
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
            ->for($user = User::factory()->create([
                'personal_identification_code' => $personalIdentificationCode = '50511246084',
            ]))
            ->create();
        InstitutionUser::factory()
            ->for($expectedInstitution2 = Institution::factory()->create())
            ->for($user)
            ->create();

        // WHEN request sent to endpoint
        $response = $this->sendGetRequest($personalIdentificationCode);

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
            ->for($user = User::factory()->create([
                'personal_identification_code' => $personalIdentificationCode = '50511246084',
            ]))
            ->create();
        InstitutionUser::factory()
            ->for($softDeletedInstitution = Institution::factory()->create())
            ->for($user)
            ->create();

        $softDeletedInstitution->delete();

        // WHEN request sent to endpoint
        $response = $this->sendGetRequest($personalIdentificationCode);

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
            ->for($user = User::factory()->create([
                'personal_identification_code' => $personalIdentificationCode = '50511246084',
            ]))
            ->create();
        InstitutionUser::factory()
            ->for(Institution::factory()->create())
            ->for($user)
            ->create()
            ->delete();

        // WHEN request sent to endpoint
        $response = $this->sendGetRequest($personalIdentificationCode);

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
            ->for(Institution::factory()->create())
            ->for($user = User::factory()->create([
                'personal_identification_code' => $personalIdentificationCode = '50511246084',
            ]))
            ->create();
        $user->delete();

        // WHEN request sent to endpoint
        $response = $this->sendGetRequest($personalIdentificationCode);

        // THEN response data should be empty
        $response
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_requesting_nonexistent_user(): void
    {
        // GIVEN the following data is in database
        InstitutionUser::factory()
            ->for(Institution::factory()->create())
            ->for(User::factory()->create([
                'personal_identification_code' => '60810202280',
            ]))->create();

        // WHEN request targets nonexistent institution user
        $response = $this->sendGetRequest('32402126598');

        // THEN response data should be empty
        $response
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_updating_user_without_access_token(): void
    {
        // GIVEN the following data is in database
        InstitutionUser::factory()
            ->for(Institution::factory()->create())
            ->for(User::factory()->create([
                'personal_identification_code' => '50511246084',
            ]))->create();

        // WHEN request sent without access token in header
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/institutions');

        // THEN response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    public function test_updating_user_when_pic_missing_in_access_token(): void
    {
        // GIVEN the following data is in database
        InstitutionUser::factory()
            ->for(Institution::factory()->create())
            ->for(User::factory()->create([
                'personal_identification_code' => '50511246084',
            ]))->create();

        // WHEN request sent without PIC in access token
        $response = $this->sendGetRequest(null);

        // THEN response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    private function sendGetRequest(?string $tokenPersonalIdentificationCode): TestResponse
    {
        $accessToken = $this->generateAccessToken([
            'personalIdentificationCode' => $tokenPersonalIdentificationCode,
        ]);

        return $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->getJson('/api/institutions');
    }
}
