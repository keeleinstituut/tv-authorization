<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;
use Throwable;

class InstitutionUserControllerUpdateTest extends TestCase
{
    use RefreshDatabase, InstitutionUserHelpers;

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
        [
            'institution' => $createdInstitution,
            'department' => $createdDepartment,
            'user' => $createdUser,
            'institutionUser' => $createdInstitutionUser,
            'roles' => [$addUserRole, $editUserRole]
        ] = $this->createBasicModels(
            email: $expectedEmail = 'test123@test.dev',
            pic: '50608024740',
            forename: 'Testjana',
            surname: $expectedSurname = 'Testjovka',
            attachInstitutionUserToDepartment: false,
            privileges: [PrivilegeKey::AddUser, PrivilegeKey::EditUser]
        );
        $viewUserRole = $this->createFactoryRole(PrivilegeKey::ViewUser, $createdInstitution->id);
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        // WHEN request sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            [
                'user' => [
                    'forename' => $expectedForename = 'Testander',
                ],
                'phone' => $expectedPhoneNumber = '+372 5678901',
                'roles' => [$editUserRole->id, $viewUserRole->id],
                'department_id' => $createdDepartment->id,
            ],
            $actingUser
        );

        // THEN the database state should be what is expected after update
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($createdInstitutionUser->id)
        );
        $expectedFragment = [
            'phone' => $expectedPhoneNumber,
            'email' => $expectedEmail,
            'roles' => Arr::map(
                [$editUserRole, $viewUserRole],
                RepresentationHelpers::createRoleNestedRepresentation(...)
            ),
            'user' => [
                ...RepresentationHelpers::createUserFlatRepresentation($createdUser),
                'forename' => $expectedForename,
                'surname' => $expectedSurname,
            ],
        ];
        $this->assertArrayHasSpecifiedFragmentIgnoringOrder($expectedFragment, $actualState);

        // And request response should correspond to the actual state
        $this->assertResponseJsonDataEqualsIgnoringOrder($actualState, $response);
    }

    /**
     * @throws Throwable
     */
    public function test_removing_roles(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels(
            privileges: [PrivilegeKey::AddUser, PrivilegeKey::EditUser]
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        // WHEN request sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            ['roles' => []],
            $actingUser
        );

        // THEN the database state should be what is expected after update
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($createdInstitutionUser->id)
        );
        $this->assertArrayHasSpecifiedFragmentIgnoringOrder(['roles' => []], $actualState);

        // And request response should correspond to the actual state
        $this->assertResponseJsonDataEqualsIgnoringOrder($actualState, $response);
    }

    /**
     * @throws Throwable
     */
    public function test_removing_department(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels();
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        // WHEN request sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            ['department_id' => null],
            $actingUser
        );

        // THEN the database state should be what is expected after update
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($createdInstitutionUser->id)
        );
        $this->assertArrayHasSpecifiedFragmentIgnoringOrder(['department' => null], $actualState);

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
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'testarok@email.tv'),
            phone: ($expectedPhone = '+372 34567890'),
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        // WHEN invalid payload is sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            [
                'email' => 'someother@email.com',
                'phone' => '+372 45678901',
                ...$invalidPayload,
            ],
            $actingUser
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertEquals($expectedPhone, InstitutionUser::findOrFail($createdInstitutionUser->id)->phone);

        // And response should indicate validation errors
        $response->assertUnprocessable();
    }

    /**
     * @dataProvider provideValidPhoneNumbers
     *
     * @throws Throwable
     */
    public function test_valid_phone_numbers(string $validPhoneNumber): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels(
            phone: '+372 50000000'
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        // WHEN request sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            ['phone' => $validPhoneNumber],
            $actingUser
        );

        // THEN the database state should have updated phone number
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($createdInstitutionUser->id)
        );
        $this->assertArrayHasSpecifiedFragmentIgnoringOrder(['phone' => $validPhoneNumber], $actualState);

        // And request response should correspond to the actual state
        $this->assertResponseJsonDataEqualsIgnoringOrder($actualState, $response);
    }

    public function test_updating_nonexistent_user(): void
    {
        // GIVEN institution has only one (acting) user
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege(
            Institution::factory(),
            PrivilegeKey::EditUser
        );

        // WHEN request targets nonexistent institution user
        $response = $this->sendPutRequestWithTokenFor(
            ($randomUuid = Str::uuid()),
            Arr::undot(['user.forename' => 'Testjalina']),
            $actingUser
        );

        // THEN database state should not change
        $this->assertDatabaseMissing(
            InstitutionUser::class,
            ['id' => $randomUuid]
        );

        // And response status should indicate resource not found
        $response->assertNotFound();
    }

    public function test_updating_user_in_another_institution(): void
    {
        // GIVEN there are two institutions and with separate users
        $targetInstitutionUser = InstitutionUser::factory()
            ->for($createdInstitutionWithUser = Institution::factory()->create())
            ->for(User::factory()->create())
            ->create([
                'email' => ($expectedEmail = 'testafana@testy.dev'),
            ]);
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege(
            $secondInstitution = Institution::factory()->create(),
            PrivilegeKey::EditUser
        );

        // WHEN request token authenticates user from one institution, but targets user from other institution
        $response = $this->sendPutRequestWithTokenFor(
            $targetInstitutionUser->id,
            ['email' => 'someother@email.com'],
            $actingUser
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($targetInstitutionUser->id)->email);
        $this->assertDatabaseHas(Institution::class, ['id' => $secondInstitution->id])
            ->assertDatabaseHas(Institution::class, ['id' => $createdInstitutionWithUser->id])
            ->assertEquals(
                [$targetInstitutionUser->id],
                InstitutionUser::whereInstitutionId($createdInstitutionWithUser->id)->get()->pluck('id')->toArray()
            );

        // And response should indicate resource is not found
        $response->assertNotFound();
    }

    /**
     * @throws Throwable
     */
    public function test_updating_user_without_privilege(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'test123@eki.ee')
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::ViewUser);

        // WHEN request sent without EDIT_USER privilege in token
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            ['email' => 'someother@email.com'],
            $actingUser
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);

        // And request response should indicate action is forbidden
        $response->assertForbidden();
    }

    /**
     * @throws Throwable
     */
    public function test_adding_role_from_another_institution(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
            'roles' => $expectedRoles
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'test321@ike.ee'),
            privileges: [PrivilegeKey::ViewUser]
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        $roleInAnotherInstitution = $this->createFactoryRole(
            PrivilegeKey::ViewRole,
            Institution::factory()->create()->id
        );

        // WHEN request input has role from another institution
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            [
                'email' => 'someother@email.com',
                'roles' => [$roleInAnotherInstitution->id],
            ],
            $actingUser
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertEqualsCanonicalizing(
            collect($expectedRoles)->pluck('id'),
            InstitutionUser::findOrFail($createdInstitutionUser->id)->roles->pluck('id')
        );

        // And request response should indicate validation errors
        $response->assertUnprocessable();
    }

    /**
     * @throws Throwable
     */
    public function test_adding_department_from_another_institution(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
            'department' => $expectedDepartment
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'test321@ike.ee'),
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        $departmentInAnotherInstitution = Department::factory()
            ->for(Institution::factory()->create())
            ->create();

        // WHEN request input has department from another institution
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            [
                'email' => 'someother@email.com',
                'department_id' => [$departmentInAnotherInstitution->id],
            ],
            $actingUser
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertEquals(
            $expectedDepartment->id,
            InstitutionUser::findOrFail($createdInstitutionUser->id)->department_id
        );

        // And request response should indicate validation errors
        $response->assertUnprocessable();
    }

    /**
     * @throws Throwable
     */
    public function test_adding_nonexistent_department(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
            'department' => $expectedDepartment
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'test321@ike.ee'),
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        // WHEN request input has nonexistent department
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            [
                'email' => 'someother@email.com',
                'department_id' => ($randomUuid = Str::uuid()),
            ],
            $actingUser
        );

        // THEN database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertEquals(
            $expectedDepartment->id,
            InstitutionUser::findOrFail($createdInstitutionUser->id)->department_id
        );
        $this->assertDatabaseMissing(
            Department::class,
            ['id' => $randomUuid]
        );

        // And request response should indicate validation errors
        $response->assertUnprocessable();
    }

    /**
     * @throws Throwable
     */
    public function test_adding_nonexistent_role(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
            'roles' => $expectedRoles
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'test321@ike.ee'),
            privileges: [PrivilegeKey::ViewUser]
        );
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);

        // WHEN request input has nonexistent role
        $response = $this->sendPutRequestWithTokenFor(
            $createdInstitutionUser->id,
            [
                'email' => 'someother@email.com',
                'roles' => [$randomUuid = Str::uuid()],
            ],
            $actingUser
        );

        // THEN database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertEqualsCanonicalizing(
            collect($expectedRoles)->pluck('id'),
            InstitutionUser::findOrFail($createdInstitutionUser->id)->roles->pluck('id')
        );
        $this->assertDatabaseMissing(
            Role::class,
            ['id' => $randomUuid]
        );

        // And request response should indicate validation errors
        $response->assertUnprocessable();
    }

    /**
     * @throws Throwable
     */
    public function test_updating_user_without_access_token(): void
    {
        // GIVEN the following data is in database
        [
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'test123@eki.ee')
        );

        // WHEN request sent without access token in header
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->putJson(
                "/api/institution-users/$createdInstitutionUser->id",
                ['email' => 'someother@email.com']
            );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);

        // And response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    private function sendPutRequestWithTokenFor(
        string $targetId,
        array $requestPayload,
        InstitutionUser $actingUser,
        array $tolkevaravClaimsOverride = []): TestResponse
    {
        return $this->sendPutRequestWithCustomToken(
            $targetId,
            $requestPayload,
            AuthHelpers::generateAccessTokenForInstitutionUser($actingUser, $tolkevaravClaimsOverride)
        );
    }

    private function sendPutRequestWithCustomToken(
        string $targetId,
        array $requestPayload,
        string $accessToken): TestResponse
    {
        return $this
            ->withHeaders(['Authorization' => "Bearer $accessToken"])
            ->putJson("/api/institution-users/$targetId", $requestPayload);
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
            ['roles' => null],
            ['roles' => ''],
            ['roles' => [null]],
            ['roles' => ['']],
            ['roles' => ['abc']],
            ['roles' => [1]],
            ['user' => null],
            ['user' => ''],
            ['user' => []],
            ['department_id' => 1],
            ['department_id' => 'abc'],
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
