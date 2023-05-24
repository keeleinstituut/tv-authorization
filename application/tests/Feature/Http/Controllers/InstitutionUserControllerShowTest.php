<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\InstitutionUserHelpers;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;

class InstitutionUserControllerShowTest extends TestCase
{
    use RefreshDatabase, InstitutionUserHelpers;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(Carbon::now());
    }

    public function test_correct_data_is_returned(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'user' => $createdUser,
            'institutionUser' => $createdInstitutionUser,
            'roles' => $expectedRoles
        ] = $this->createBasicModels(
            email: $expectedEmail = 'test123@test.dev',
            phone: $expectedPhone = '+372 7777777',
            pic: $expectedPic = '50608024740',
            forename: $expectedForename = 'Testjana',
            surname: $expectedSurname = 'Testjovka',
            attachInstitutionUserToDepartment: false,
            privileges: [PrivilegeKey::ViewUser, PrivilegeKey::EditUser]
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::ViewUser);

        // WHEN request sent to endpoint
        $response = $this->sendGetRequestWithTokenFor($createdInstitutionUser->id, $actingUser);

        // THEN request response should correspond to the expected state
        $expectedResponseData = [
            ...RepresentationHelpers::createInstitutionUserNestedRepresentation(
                $createdInstitutionUser->refresh()
            ),
            'phone' => $expectedPhone,
            'email' => $expectedEmail,
            'roles' => Arr::map(
                $expectedRoles,
                RepresentationHelpers::createRoleNestedRepresentation(...)
            ),
            'user' => [
                ...RepresentationHelpers::createUserFlatRepresentation($createdUser),
                'forename' => $expectedForename,
                'surname' => $expectedSurname,
                'personal_identification_code' => $expectedPic,
            ],
        ];
        $this->assertResponseJsonDataIsEqualTo($expectedResponseData, $response);
    }

    public function test_requesting_nonexistent_user(): void
    {
        // GIVEN institution with single (acting) user
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege(Institution::factory(), PrivilegeKey::ViewUser);

        // WHEN request targets nonexistent institution user
        $response = $this->sendGetRequestWithTokenFor(Str::orderedUuid(), $actingUser);

        // THEN response status should indicate resource was not found
        $response->assertNotFound();
    }

    public function test_requesting_soft_deleted_institution_user(): void
    {
        // GIVEN a soft-deleted institution user is in the created institution
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels();
        $createdInstitutionUser->delete();
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::ViewUser);

        // WHEN request targets the soft-deleted institution user
        $response = $this->sendGetRequestWithTokenFor($createdInstitutionUser->id, $actingUser);

        // THEN response status should indicate resource was not found
        $response->assertNotFound();
    }

    public function test_requesting_institution_user_when_user_soft_deleted(): void
    {
        // GIVEN a soft-deleted institution user is in the created institution
        [
            'institution' => $createdInstitution,
            'user' => $createdUser,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels();
        $createdUser->delete();
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::ViewUser);

        // WHEN request targets the institution user with a soft-deleted user relation*
        $response = $this->sendGetRequestWithTokenFor($createdInstitutionUser->id, $actingUser);

        // THEN response status should indicate resource was not found
        $response->assertNotFound();
    }

    public function test_requesting_user_in_another_institution(): void
    {
        // GIVEN there are two institutions, one of them with target user, one with acting user
        $targetInstitutionUser = InstitutionUser::factory()
            ->for(Institution::factory())
            ->for(User::factory())
            ->create();
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege(Institution::factory(), PrivilegeKey::ViewUser);

        // WHEN request token contains user from one institution, but targets user from other institution
        $response = $this->sendGetRequestWithTokenFor($targetInstitutionUser->id, $actingUser);

        // THEN response should indicate resource is not found
        $response->assertNotFound();
    }

    public function test_requesting_user_without_privilege(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels();
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::SetUserVacation);

        // WHEN request sent without VIEW_USER privilege in token
        $response = $this->sendGetRequestWithTokenFor($createdInstitutionUser->id, $actingUser);

        // THEN response should indicate action is forbidden
        $response->assertForbidden();
    }

    public function test_updating_user_without_access_token(): void
    {
        // GIVEN the following data is in database
        ['institutionUser' => $createdInstitutionUser] = $this->createBasicModels();

        // WHEN request sent without access token in header
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson(
                "/api/institution-users/$createdInstitutionUser->id"
            );

        // THEN response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    private function sendGetRequestWithTokenFor(
        string $targetId,
        InstitutionUser $institutionUser,
        array $tolkevaravClaimsOverride = []): TestResponse
    {
        $token = $this->generateAccessToken([
            ...$this->makeTolkevaravClaimsForInstitutionUser($institutionUser),
            ...$tolkevaravClaimsOverride,
        ]);

        return $this->sendGetRequestWithCustomToken($targetId, $token);
    }

    private function sendGetRequestWithCustomToken(
        string $targetId,
        string $accessToken): TestResponse
    {
        return $this
            ->withHeaders(['Authorization' => "Bearer $accessToken"])
            ->getJson("/api/institution-users/$targetId");
    }
}
