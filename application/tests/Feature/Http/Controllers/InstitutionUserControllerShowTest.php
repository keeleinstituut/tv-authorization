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
use Tests\AuthHelpers;
use Tests\Feature\ModelHelpers;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;

class InstitutionUserControllerShowTest extends TestCase
{
    use RefreshDatabase, ModelHelpers;

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
            privileges: [PrivilegeKey::AddUser, PrivilegeKey::EditUser]
        );

        // WHEN request sent to endpoint
        $response = $this->sendGetRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id
        );

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
        // GIVEN institution has no users
        $createdInstitution = Institution::factory()->create();

        // WHEN request targets nonexistent institution user
        $response = $this->sendGetRequest(
            Str::uuid(),
            $createdInstitution->id
        );

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

        // WHEN request targets the soft-deleted institution user
        $response = $this->sendGetRequest(
            $createdInstitutionUser,
            $createdInstitution->id
        );

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

        // WHEN request targets the institution user with a soft-deleted user relation*
        $response = $this->sendGetRequest(
            $createdInstitutionUser,
            $createdInstitution->id
        );

        // THEN response status should indicate resource was not found
        $response->assertNotFound();
    }

    public function test_requesting_user_in_another_institution(): void
    {
        // GIVEN there are two institutions and only one of them has users
        $createdInstitutionWithoutUsers = Institution::factory()->create();
        $createdInstitutionUser = InstitutionUser::factory()
            ->for(Institution::factory()->create())
            ->for(User::factory()->create())
            ->create();

        // WHEN request token contains institution without users, but targets user from other institution
        $response = $this->sendGetRequest(
            $createdInstitutionUser->id,
            $createdInstitutionWithoutUsers->id
        );

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

        // WHEN request sent without VIEW_USER privilege in token
        $response = $this->sendGetRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            [PrivilegeKey::EditUserVacation]
        );

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

    /**
     * @param  array<PrivilegeKey>  $tokenPrivileges
     */
    private function sendGetRequest(string $routeId,
        string $tokenInstitution,
        array $tokenPrivileges = [PrivilegeKey::ViewUser]): TestResponse
    {
        return $this
            ->withHeaders(AuthHelpers::createJsonHeaderWithTokenParams($tokenInstitution, $tokenPrivileges))
            ->getJson("/api/institution-users/$routeId");
    }
}
