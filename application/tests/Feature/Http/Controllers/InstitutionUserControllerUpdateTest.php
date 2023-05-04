<?php

/** @noinspection PhpUnhandledExceptionInspection */

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
use Tests\Feature\ModelHelpers;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;

class InstitutionUserControllerUpdateTest extends TestCase
{
    use RefreshDatabase, ModelHelpers;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(Carbon::now());
    }

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
            email: 'test123@test.dev',
            pic: '50608024740',
            forename: 'Testjana',
            surname: 'Testjovka',
            attachInstitutionUserToDepartment: false,
            privileges: [PrivilegeKey::AddUser, PrivilegeKey::EditUser]
        );
        $viewUserRole = $this->createFactoryRole(PrivilegeKey::ViewUser, $createdInstitution->id);

        // WHEN request sent to endpoint
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            [
                'user' => [
                    'forename' => 'Testander',
                ],
                'phone' => '+372 1234 5678',
                'roles' => [$editUserRole->id, $viewUserRole->id],
                'department_id' => $createdDepartment->id,
            ]
        );

        // THEN the database state should be what is expected after update
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($createdInstitutionUser->id)
        );
        $expectedFragment = [
            'phone' => '+372 1234 5678',
            'email' => 'test123@test.dev',
            'roles' => Arr::map(
                [$editUserRole, $viewUserRole],
                RepresentationHelpers::createRoleNestedRepresentation(...)
            ),
            'user' => [
                ...RepresentationHelpers::createUserFlatRepresentation($createdUser),
                'forename' => 'Testander',
                'surname' => 'Testjovka',
            ],
        ];
        $this->assertArrayHasSpecifiedFragment($expectedFragment, $actualState);

        // And request response should correspond to the actual state
        $this->assertResponseJsonDataIsEqualTo($actualState, $response);
    }

    public function test_removing_roles(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels(
            privileges: [PrivilegeKey::AddUser, PrivilegeKey::EditUser]
        );

        // WHEN request sent to endpoint
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            ['roles' => []]
        );

        // THEN the database state should be what is expected after update
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($createdInstitutionUser->id)
        );
        $this->assertArrayHasSpecifiedFragment(['roles' => []], $actualState);

        // And request response should correspond to the actual state
        $this->assertResponseJsonDataIsEqualTo($actualState, $response);
    }

    public function test_removing_department(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels();

        // WHEN request sent to endpoint
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            ['department_id' => null]
        );

        // THEN the database state should be what is expected after update
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($createdInstitutionUser->id)
        );
        $this->assertArrayHasSpecifiedFragment(['department' => null], $actualState);

        // And request response should correspond to the actual state
        $this->assertResponseJsonDataIsEqualTo($actualState, $response);
    }

    /**
     * @dataProvider provideInvalidRequestPayloads
     */
    public function test_request_validation(array $invalidPayload): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'testarok@email.tv'),
            phone: ($expectedPhone = '+372 1234 5678'),
        );

        // WHEN invalid payload is sent to endpoint
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            [
                'email' => 'someother@email.com',
                'phone' => '+372 9876 5432',
                ...$invalidPayload,
            ]
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertEquals($expectedPhone, InstitutionUser::findOrFail($createdInstitutionUser->id)->phone);

        // And response should indicate validation errors
        $response->assertUnprocessable();
    }

    public function test_updating_nonexistant_user(): void
    {
        // GIVEN institution has no users
        $createdInstitution = Institution::factory()->create();

        // WHEN request targets nonexistant insitution user
        $response = $this->sendPutRequest(
            ($randomUuid = Str::uuid()),
            $createdInstitution->id,
            Arr::undot(['user.forename' => 'Testjalina'])
        );

        // THEN database state should not change
        $this->assertDatabaseMissing(
            InstitutionUser::class,
            ['institution_id' => $createdInstitution->id]
        )->assertDatabaseMissing(
            InstitutionUser::class,
            ['id' => $randomUuid]
        );

        // And response status should indicate resource not found
        $response->assertNotFound();
    }

    public function test_updating_user_in_another_institution(): void
    {
        // GIVEN there are two institutions and only one of them has users
        $createdInstitutionWithoutUsers = Institution::factory()->create();
        $createdInstitutionUser = InstitutionUser::factory()
            ->for($createdInstitutionWithUser = Institution::factory()->create())
            ->for(User::factory()->create())
            ->create([
                'email' => ($expectedEmail = 'testafana@testy.dev'),
            ]);

        // WHEN request token contains institution without users, but targets user from other institution
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitutionWithoutUsers->id,
            ['email' => 'someother@email.com']
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertDatabaseHas(Institution::class, ['id' => $createdInstitutionWithoutUsers->id])
            ->assertDatabaseMissing(InstitutionUser::class, ['institution_id' => $createdInstitutionWithoutUsers->id])
            ->assertDatabaseHas(Institution::class, ['id' => $createdInstitutionWithUser->id])
            ->assertEquals(
                [$createdInstitutionUser->id],
                InstitutionUser::whereInstitutionId($createdInstitutionWithUser->id)->get()->pluck('id')->toArray()
            );

        // And request response should indicate action is forbidden
        $response->assertForbidden();
    }

    public function test_updating_user_without_privilege(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'test123@eki.ee')
        );

        // WHEN request sent without EDIT_USER privilege in token
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            ['email' => 'someother@email.com'],
            [PrivilegeKey::ViewUser]
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);

        // And request response should indicate action is forbidden
        $response->assertForbidden();
    }

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

        $roleInAnotherInstitution = $this->createFactoryRole(
            PrivilegeKey::ViewRole,
            Institution::factory()->create()->id
        );

        // WHEN request input has role from another institution
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            [
                'email' => 'someother@email.com',
                'roles' => [$roleInAnotherInstitution->id],
            ]
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertEquals(
            collect($expectedRoles)->pluck('id')->toArray(),
            InstitutionUser::findOrFail($createdInstitutionUser->id)->roles->pluck('id')->toArray()
        );

        // And request response should indicate validation errors
        $response->assertUnprocessable();
    }

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

        $departmentInAnotherInstitution = Department::factory()
            ->for(Institution::factory()->create())
            ->create();

        // WHEN request input has department from another institution
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            [
                'email' => 'someother@email.com',
                'department_id' => [$departmentInAnotherInstitution->id],
            ]
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

    public function test_adding_nonexistant_department(): void
    {
        // GIVEN the following data is in database
        [
            'institution' => $createdInstitution,
            'institutionUser' => $createdInstitutionUser,
            'department' => $expectedDepartment
        ] = $this->createBasicModels(
            email: ($expectedEmail = 'test321@ike.ee'),
        );

        // WHEN request input has nonexistant department
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            [
                'email' => 'someother@email.com',
                'department_id' => ($randomUuid = Str::uuid()),
            ]
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

    public function test_adding_nonexistant_role(): void
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

        // WHEN request input has nonexistant role
        $response = $this->sendPutRequest(
            $createdInstitutionUser->id,
            $createdInstitution->id,
            [
                'email' => 'someother@email.com',
                'roles' => [$randomUuid = Str::uuid()],
            ]
        );

        // THEN database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($createdInstitutionUser->id)->email);
        $this->assertEquals(
            collect($expectedRoles)->pluck('id')->toArray(),
            InstitutionUser::findOrFail($createdInstitutionUser->id)->roles->pluck('id')->toArray()
        );
        $this->assertDatabaseMissing(
            Role::class,
            ['id' => $randomUuid]
        );

        // And request response should indicate validation errors
        $response->assertUnprocessable();
    }

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

    /**
     * @param  array<PrivilegeKey>  $tokenPrivileges
     */
    private function sendPutRequest(string $routeId,
        string $tokenInstitution,
        array $requestPayload,
        array $tokenPrivileges = [PrivilegeKey::EditUser]): TestResponse
    {
        return $this
            ->withHeaders($this->createJsonHeaderWithTokenParams($tokenInstitution, $tokenPrivileges))
            ->putJson("/api/institution-users/$routeId", $requestPayload);
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
}
