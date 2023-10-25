<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;
use Throwable;

class InstitutionUserControllerUpdateCurrentTest extends TestCase
{
    use RefreshDatabase, InstitutionUserHelpers, ModelAssertions;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(Carbon::now());
    }

    /**
     * @throws Throwable
     */
    public function test_fields_are_updated(): void
    {
        // GIVEN the following data is in database
        $createdInstitution = Institution::factory()->create();
        $actingInstitutionUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::ViewUser);

        // WHEN request sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            [
                'user' => [
                    'forename' => $expectedForename = 'Forename',
                    'surname' => $expectedSurname = 'Surname',
                ],
                'phone' => $expectedPhoneNumber = '+372 5678901',
                'email' => $expectedEmail = 'new@email.com',
            ],
            $actingInstitutionUser
        );

        // THEN the database state should be what is expected after update
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($actingInstitutionUser->id)
        );

        $expectedFragment = [
            'phone' => $expectedPhoneNumber,
            'email' => $expectedEmail,
            'user' => [
                ...RepresentationHelpers::createUserFlatRepresentation($actingInstitutionUser->user),
                'forename' => $expectedForename,
                'surname' => $expectedSurname,
            ],
        ];
        $this->assertArrayHasSubsetIgnoringOrder($expectedFragment, $actualState);

        // And request response should correspond to the actual state
        $this->assertResponseJsonDataEqualsIgnoringOrder($actualState, $response);
    }

    /**
     * @dataProvider provideInvalidRequestPayloads
     *
     * @throws Throwable
     */
    public function test_request_validation(array $invalidPayload): void
    {
        // GIVEN the following data is in database
        $createdInstitution = Institution::factory()->create();
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);
        $expectedEmail = $actingUser->email;
        $expectedPhone = $actingUser->phone;
        // WHEN invalid payload is sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            [
                'email' => 'someother@email.com',
                'phone' => '+372 45678901',
                ...$invalidPayload,
            ],
            $actingUser
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($actingUser->id)->email);
        $this->assertEquals($expectedPhone, InstitutionUser::findOrFail($actingUser->id)->phone);

        // And response should indicate validation errors
        $response->assertUnprocessable();
    }

    /**
     * @throws Throwable
     */
    public function test_updating_user_without_access_token(): void
    {

        // WHEN request sent without access token in header
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->putJson(
                '/api/institution-users',
                ['email' => 'someother@email.com']
            );

        // And response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    private function sendPutRequestWithTokenFor(
        array $requestPayload,
        InstitutionUser $actingUser,
        array $tolkevaravClaimsOverride = []): TestResponse
    {
        return $this->sendPutRequestWithCustomToken(
            $requestPayload,
            AuthHelpers::generateAccessTokenForInstitutionUser($actingUser, $tolkevaravClaimsOverride)
        );
    }

    private function sendPutRequestWithCustomToken(
        array $requestPayload,
        string $accessToken): TestResponse
    {
        return $this
            ->withHeaders(['Authorization' => "Bearer $accessToken"])
            ->putJson('/api/institution-users', $requestPayload);
    }

    /**
     * @return array<array<array>>
     */
    public static function provideInvalidRequestPayloads(): array
    {
        return collect([
            ['email' => null],
            ['email' => ''],
            ['email' => 'not-email'],
            ['phone' => null],
            ['phone' => ''],
            ['phone' => '-1'],
            ['phone' => 'abc'],
            ['phone' => '5123456'],
            ['phone' => '5123 456'],
            ['phone' => '51234567'],
            ['phone' => '5123 4567'],
            ['phone' => '37251234567'],
            ['phone' => '372 5123 4567'],
            ['phone' => '372 5123 456'],
            ['phone' => '0037251234567'],
            ['phone' => '003725123456'],
            ['phone' => '00 372 5123 4567'],
            ['phone' => '00 372 5123 456'],
            ['phone' => '123'],
            ['phone' => '1234567'],
            ['phone' => '1234 567'],
            ['phone' => '12345678'],
            ['phone' => '1234 5678'],
            ['phone' => '+37201234567'],
            ['phone' => '+372 0123 4567'],
            ['phone' => '+37212345678'],
            ['phone' => '+372 1234 5678'],
            ['phone' => '+37223456789'],
            ['phone' => '+372 2345 6789'],
            ['phone' => '+37289012345'],
            ['phone' => '+372 8901 2345'],
            ['phone' => '+37290123456'],
            ['phone' => '+372 9012 3456'],
            ['phone' => '+372567890'],
            ['phone' => '+372 5678 90'],
            ['phone' => '+372 5678 901'],
            ['phone' => '+372 5678 9012'],
            ['phone' => '+372567890123'],
            ['phone' => '372 5678 9012 3'],
            ['phone' => '+372 5 6 7 8 9 0'],
            ['phone' => '+3 7 2 5 6 7 8 9 0'],
            ['phone' => '+ 372 567890'],
            ['phone' => ' +372 567890'],
            ['user' => null],
            ['user' => ''],
            ['user' => []],
            ['user.forename' => ''],
            ['user.forename' => null],
            ['user.surname' => ''],
            ['user.surname' => null],
        ])
            ->mapWithKeys(fn ($payload) => [json_encode($payload) => $payload]) // for test reports - otherwise only param index is reported
            ->map(Arr::undot(...))
            ->map(fn ($payload) => [$payload])
            ->toArray();
    }

    /**
     * @return array<array<array>>
     */
    public static function provideValidPhoneNumbers(): array
    {
        return collect([
            '+37234567890',
            '+372 34567890',
            '+37245678901',
            '+372 45678901',
            '+37256789012',
            '+372 56789012',
            '+37267890123',
            '+372 67890123',
            '+37278901234',
            '+372 78901234',
            '+3723456789',
            '+372 3456789',
            '+3724567890',
            '+372 4567890',
            '+3725678901',
            '+372 5678901',
            '+3726789012',
            '+372 6789012',
            '+3727890123',
            '+372 7890123',
        ])
            ->mapWithKeys(fn ($phone) => [$phone => $phone]) // for test reports - otherwise only param index is reported
            ->map(fn ($phone) => [$phone])
            ->toArray();
    }
}
