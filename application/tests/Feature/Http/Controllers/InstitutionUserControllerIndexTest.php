<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\InstitutionUserStatus;
use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserController;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Privilege;
use App\Models\Role;
use Closure;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Testing\AssertableJsonString;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;

class InstitutionUserControllerIndexTest extends TestCase
{
    use RefreshDatabase, EntityHelpers, InstitutionUserHelpers;

    public function test_list_of_institution_users_returned(): void
    {
        $institution = $this->createInstitution();
        $institutionUsers = InstitutionUser::factory(9)->for($institution)
            ->has(InstitutionUserRole::factory(2))->create();

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::ViewUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $response = $this->queryInstitutionUsers($accessToken);

        $response->assertStatus(Response::HTTP_OK);
        foreach ($institutionUsers as $institutionUser) {
            $response->assertJsonFragment(
                RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser)
            );
        }

        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 10,
                'total' => 10,
            ],
        ]);
    }

    public function test_list_of_institution_users_sortable(): void
    {
        $institution = $this->createInstitution();
        $institutionUsers = InstitutionUser::factory(9)->for($institution)
            ->has(InstitutionUserRole::factory(2))->create();

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::ViewUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $response = $this->queryInstitutionUsers(
            $accessToken,
            ['sort_by' => 'name', 'sort_order' => 'asc'],
        );

        $response->assertStatus(Response::HTTP_OK);
        $institutionUsersData = json_decode($response->content(), true);

        $this->assertEquals(
            $institutionUsers->push($actingInstitutionUser)->sortBy('user.surname')->pluck('user.surname')->toArray(),
            collect($institutionUsersData['data'])->pluck('user.surname')->toArray()
        );
    }

    private function createDifferentInstitutionUsersInNewInstitution(): array
    {
        $institution = Institution::factory()->create()->refresh();
        $departments = Department::factory(3)->for($institution)->create()->each->refresh();
        $roles = Role::factory(3)->for($institution)->create()->each->refresh();
        $actingInstitutionUser = $this->createUserInGivenInstitutionWithGivenPrivilege($institution, PrivilegeKey::ViewUser)->refresh();

        $activeInstitutionUserWithNoRolesNoDepartment = InstitutionUser::factory()->for($institution)->create()->refresh();
        $activeInstitutionUserWithNoRolesDepartment0 = InstitutionUser::factory()->for($institution)->for($departments[0])->create()->refresh();
        $activeInstitutionUserWithRole0Department0 = InstitutionUser::factory()->for($institution)->hasAttached($roles[0])->for($departments[0])->create()->refresh();
        $activeInstitutionUserWithRole1NoDepartment = InstitutionUser::factory()->for($institution)->hasAttached($roles[1])->create()->refresh();

        $deactivatedInstitutionUserWithNoRolesNoDepartment = InstitutionUser::factory()->for($institution)->create(['deactivation_date' => Date::yesterday()])->refresh();
        $deactivatedInstitutionUserWithNoRolesDepartment0 = InstitutionUser::factory()->for($institution)->for($departments[0])->create(['deactivation_date' => Date::yesterday()])->refresh();
        $deactivatedInstitutionUserWithRole0Department1 = InstitutionUser::factory()->for($institution)->hasAttached($roles[0])->for($departments[1])->create(['deactivation_date' => Date::yesterday()])->refresh();
        $deactivatedInstitutionUserWithRole1NoDepartment = InstitutionUser::factory()->for($institution)->hasAttached($roles[1])->create(['deactivation_date' => Date::yesterday()])->refresh();

        $archivedInstitutionUserWithNoRolesNoDepartment = InstitutionUser::factory()->for($institution)->create(['archived_at' => Date::yesterday()])->refresh();
        $archivedInstitutionUserWithNoRolesDepartment1 = InstitutionUser::factory()->for($institution)->for($departments[1])->create(['archived_at' => Date::yesterday()])->refresh();
        $archivedInstitutionUserWithRole1Department0 = InstitutionUser::factory()->for($institution)->hasAttached($roles[1])->for($departments[0])->create(['archived_at' => Date::yesterday()])->refresh();
        $archivedInstitutionUserWithRole0NoDepartment = InstitutionUser::factory()->for($institution)->hasAttached($roles[0])->create(['archived_at' => Date::yesterday()])->refresh();

        return [
            'institution' => $institution,
            'departments' => $departments,
            'roles' => $roles,
            'actingUser' => $actingInstitutionUser,
            'usersByStatus' => [
                'active' => [
                    $actingInstitutionUser,
                    $activeInstitutionUserWithNoRolesNoDepartment,
                    $activeInstitutionUserWithNoRolesDepartment0,
                    $activeInstitutionUserWithRole0Department0,
                    $activeInstitutionUserWithRole1NoDepartment,
                ],
                'deactivated' => [
                    $deactivatedInstitutionUserWithNoRolesNoDepartment,
                    $deactivatedInstitutionUserWithNoRolesDepartment0,
                    $deactivatedInstitutionUserWithRole0Department1,
                    $deactivatedInstitutionUserWithRole1NoDepartment,
                ],
                'archived' => [
                    $archivedInstitutionUserWithNoRolesNoDepartment,
                    $archivedInstitutionUserWithNoRolesDepartment1,
                    $archivedInstitutionUserWithRole1Department0,
                    $archivedInstitutionUserWithRole0NoDepartment,
                ],
            ],
            'usersByRoleIndex' => [
                0 => [
                    $activeInstitutionUserWithRole0Department0,
                    $deactivatedInstitutionUserWithRole0Department1,
                    $archivedInstitutionUserWithRole0NoDepartment,
                ],
                1 => [
                    $activeInstitutionUserWithRole1NoDepartment,
                    $deactivatedInstitutionUserWithRole1NoDepartment,
                    $archivedInstitutionUserWithRole1Department0,
                ],
                2 => [],
            ],
            'usersByDepartmentIndex' => [
                0 => [
                    $activeInstitutionUserWithNoRolesDepartment0,
                    $activeInstitutionUserWithRole0Department0,
                    $deactivatedInstitutionUserWithNoRolesDepartment0,
                    $archivedInstitutionUserWithRole1Department0,
                ],
                1 => [
                    $deactivatedInstitutionUserWithRole0Department1,
                    $archivedInstitutionUserWithNoRolesDepartment1,
                ],
                2 => [],
            ],
        ];
    }

    /**
     * The "definitions" or "locations" refer to data in the array returned by @link createDifferentInstitutionUsersInNewInstitution
     *
     * @return array<array{
     *     queryParamsBuildDefinition: array<string, string|array<string>>,
     *     expectedResponseDataBuildDefinition: array<string>,
     * }> */
    public static function provideQueryParamsAndExpectedResponseDataBuildDefinitions(): array
    {
        return [
            'No filtering' => [
                'queryParamsBuildDefinition' => [],
                'expectedResponseDataBuildDefinition' => ['usersByStatus.active', 'usersByStatus.deactivated', 'usersByStatus.archived'],
            ],
            'Filtering by statuses: ACTIVE' => [
                'queryParamsBuildDefinition' => ['statuses' => ['ACTIVE']],
                'expectedResponseDataBuildDefinition' => ['usersByStatus.active'],
            ],
            'Filtering by statuses: DEACTIVATED' => [
                'queryParamsBuildDefinition' => ['statuses' => ['DEACTIVATED']],
                'expectedResponseDataBuildDefinition' => ['usersByStatus.deactivated'],
            ],
            'Filtering by statuses: ARCHIVED' => [
                'queryParamsBuildDefinition' => ['statuses' => ['ARCHIVED']],
                'expectedResponseDataBuildDefinition' => ['usersByStatus.archived'],
            ],
            'Filtering by statuses: ACTIVE or DEACTIVATED' => [
                'queryParamsBuildDefinition' => ['statuses' => ['ACTIVE', 'DEACTIVATED']],
                'expectedResponseDataBuildDefinition' => ['usersByStatus.active', 'usersByStatus.deactivated'],
            ],
            'Filtering by statuses: DEACTIVATED or ARCHIVED' => [
                'queryParamsBuildDefinition' => ['statuses' => ['DEACTIVATED', 'ARCHIVED']],
                'expectedResponseDataBuildDefinition' => ['usersByStatus.deactivated', 'usersByStatus.archived'],
            ],
            'Filtering by roles: role at index 0' => [
                'queryParamsBuildDefinition' => ['roles' => ['roles.0.id']],
                'expectedResponseDataBuildDefinition' => ['usersByRoleIndex.0'],
            ],
            'Filtering by roles: role at index 1' => [
                'queryParamsBuildDefinition' => ['roles' => ['roles.1.id']],
                'expectedResponseDataBuildDefinition' => ['usersByRoleIndex.1'],
            ],
            'Filtering by roles: role at index 2' => [
                'queryParamsBuildDefinition' => ['roles' => ['roles.2.id']],
                'expectedResponseDataBuildDefinition' => ['usersByRoleIndex.2'],
            ],
            'Filtering by roles: role at index 0 or role at index 1' => [
                'queryParamsBuildDefinition' => ['roles' => ['roles.0.id', 'roles.1.id']],
                'expectedResponseDataBuildDefinition' => ['usersByRoleIndex.0', 'usersByRoleIndex.1'],
            ],
            'Filtering by departments: department at index 0' => [
                'queryParamsBuildDefinition' => ['departments' => ['departments.0.id']],
                'expectedResponseDataBuildDefinition' => ['usersByDepartmentIndex.0'],
            ],
            'Filtering by departments: department at index 1' => [
                'queryParamsBuildDefinition' => ['departments' => ['departments.1.id']],
                'expectedResponseDataBuildDefinition' => ['usersByDepartmentIndex.1'],
            ],
            'Filtering by departments: department at index 2' => [
                'queryParamsBuildDefinition' => ['departments' => ['departments.2.id']],
                'expectedResponseDataBuildDefinition' => ['usersByDepartmentIndex.2'],
            ],
            'Filtering by departments: department at index 0 or department at index 1' => [
                'queryParamsBuildDefinition' => ['departments' => ['departments.0.id', 'departments.1.id']],
                'expectedResponseDataBuildDefinition' => ['usersByDepartmentIndex.0', 'usersByDepartmentIndex.1'],
            ],
            'Filtering by multiple conditions: DEACTIVATED and role at index 0 and department at index 1' => [
                'queryParamsBuildDefinition' => [
                    'statuses' => ['DEACTIVATED'],
                    'roles' => ['roles.0.id'],
                    'departments' => ['departments.1.id'],
                ],
                'expectedResponseDataBuildDefinition' => ['usersByStatus.deactivated.2'],
            ],
            'Filtering by multiple conditions: ACTIVE or ARCHIVED and role at index 0 or role at index 1 and department at index 0' => [
                'queryParamsBuildDefinition' => [
                    'statuses' => ['ACTIVE', 'ARCHIVED'],
                    'roles' => ['roles.0.id', 'roles.1.id'],
                    'departments' => ['departments.0.id'],
                ],
                'expectedResponseDataBuildDefinition' => ['usersByDepartmentIndex.0.1', 'usersByDepartmentIndex.0.3'],
            ],

        ];
    }

    /** @dataProvider provideQueryParamsAndExpectedResponseDataBuildDefinitions
     * @param  array<string, string|array<string>>  $queryParamsBuildDefinition
     * @param  array<string>  $expectedResponseDataBuildDefinition
     */
    public function test_filtering_institution_users(
        array $queryParamsBuildDefinition,
        array $expectedResponseDataBuildDefinition): void
    {
        $createdData = $this->createDifferentInstitutionUsersInNewInstitution();

        $queryParameters = collect($queryParamsBuildDefinition)
            ->map(fn (array $paramDefinitionOrValues) => collect($paramDefinitionOrValues)
                ->map(fn ($itemDefinitionOrValue) => Arr::get($createdData, $itemDefinitionOrValue, $itemDefinitionOrValue))
                ->all()
            )
            ->put('per_page', 100)
            ->all();

        $expectedResponseData = collect($expectedResponseDataBuildDefinition)
            ->flatMap(fn ($institutionUsersLocation) => Arr::wrap(Arr::get($createdData, $institutionUsersLocation)))
            ->unique('id')
            ->map(RepresentationHelpers::createInstitutionUserNestedRepresentation(...))
            ->all();

        $response = $this->sendIndexRequestWithExpectedHeaders($queryParameters, $createdData['actingUser']);

        $responseDataJson = new AssertableJsonString($response->json('data'));
        $responseDataJson->assertSimilar($expectedResponseData);

        $response->assertOk();
    }

    public function test_list_of_institution_filtered_by_role(): void
    {
        $institution = $this->createInstitution();
        $role1 = $this->createRoleWithPrivileges($institution, [PrivilegeKey::AddUser]);
        $role2 = $this->createRoleWithPrivileges($institution, [PrivilegeKey::AddUser]);

        InstitutionUser::factory(10)->for($institution)
            ->has(
                InstitutionUserRole::factory()->state(new Sequence(
                    ['role_id' => $role1->id],
                    ['role_id' => $role2->id],
                ))
            )->create();

        $role = Role::where('institution_id', '=', $institution->id)
            ->first();

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::ViewUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $response = $this->queryInstitutionUsers(
            $accessToken,
            ['roles' => [$role->id]],
        );

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'total' => InstitutionUserRole::where('role_id', '=', $role->id)->count(),
                ],
            ]);
    }

    public function test_list_of_institution_filtered_by_status(): void
    {
        $institution = $this->createInstitution();
        InstitutionUser::factory(9)
            ->for($institution)
            ->sequence(
                ['deactivation_date' => Date::now()->subMonth()->format('Y-m-d')],
                []
            )
            ->has(InstitutionUserRole::factory(2))
            ->create();

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::ViewUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $response = $this->queryInstitutionUsers(
            $accessToken,
            ['statuses' => [InstitutionUserStatus::Active->value]]
        );

        $response->assertStatus(Response::HTTP_OK)->assertJson([
            'meta' => [
                'total' => 5,
            ],
        ]);
    }

    public function test_list_of_institution_returned_50_results_per_page(): void
    {
        $institution = $this->createInstitution();
        InstitutionUser::factory(51)
            ->for($institution)
            ->has(InstitutionUserRole::factory(2))
            ->create();

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::ViewUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $response = $this->queryInstitutionUsers(
            $accessToken,
            ['per_page' => 50]
        );

        $response->assertStatus(Response::HTTP_OK)->assertJson([
            'meta' => [
                'per_page' => 50,
            ],
        ]);
    }

    public function test_unauthorized_access_to_list_of_institution_users_returned_403(): void
    {
        $response = $this->queryInstitutionUsers();
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @return array<array{ Closure(array): array }> */
    public static function provideQueryParamInvalidators(): array
    {
        return [
            'per_page=15' => [fn ($params) => [
                ...$params,
                'per_page' => 15,
            ]],
            'sort_by=unknown' => [fn ($params) => [
                ...$params,
                'sort_by' => 'unknown',
            ]],
            'sort_order' => [fn ($params) => [
                ...$params,
                'sort_order' => 'unknown',
            ]],
            'roles not an array' => [fn ($params) => [
                ...$params,
                'roles' => collect($params['roles'])->join(','),
            ]],
            'role from another institution' => [fn ($params) => [
                ...$params,
                'roles' => [$params['roles'][0], Role::factory()->create()->id],
            ]],
            'non-existent role' => [fn ($params) => [
                ...$params,
                'roles' => [$params['roles'][0], Str::uuid()->toString()],
            ]],
            'departments not an array' => [fn ($params) => [
                ...$params,
                'departments' => collect($params['departments'])->join(','),
            ]],
            'department from another institution' => [fn ($params) => [
                ...$params,
                'departments' => [$params['departments'][0], Department::factory()->create()->id],
            ]],
            'non-existent department' => [fn ($params) => [
                ...$params,
                'departments' => [$params['departments'][0], Str::uuid()->toString()],
            ]],
            'statuses not an array' => [fn ($params) => [
                ...$params,
                'statuses' => collect(InstitutionUserStatus::cases())
                    ->map(fn ($status) => $status->value)
                    ->join(','),
            ]],
            'unknown status' => [fn ($params) => [
                ...$params,
                'statuses' => [InstitutionUserStatus::Active->value, 'BAMBOOZLED'],
            ]],
        ];
    }

    /**
     * @dataProvider provideQueryParamInvalidators
     *
     * @param  Closure(array): array  $invalidateQueryParameters
     */
    public function test_invalid_parameters_causes_422(Closure $invalidateQueryParameters): void
    {
        [
            'departments' => $departments,
            'roles' => $roles,
            'actingUser' => $actingUser,
        ] = $this->createDifferentInstitutionUsersInNewInstitution();

        $correctQueryParameters = $this->createExampleQueryParameters($roles, $departments);
        $invalidQueryParameters = $invalidateQueryParameters($correctQueryParameters);

        $this->sendIndexRequestWithExpectedHeaders($invalidQueryParameters, $actingUser)
            ->assertJsonMissingPath('data')
            ->assertUnprocessable();
    }

    public function test_correct_parameters_dont_cause_422(): void
    {
        [
            'departments' => $departments,
            'roles' => $roles,
            'actingUser' => $actingUser,
        ] = $this->createDifferentInstitutionUsersInNewInstitution();

        $queryParams = $this->createExampleQueryParameters($roles, $departments);
        $this->sendIndexRequestWithExpectedHeaders($queryParams, $actingUser)->assertOk();
    }

    private function queryInstitutionUsers(?string $accessToken = null, ?array $queryParams = null): TestResponse
    {
        if (! empty($accessToken)) {
            $this->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ]);
        }

        return $this->getJson(action(
            [InstitutionUserController::class, 'index'],
            $queryParams ?: []
        ));
    }

    private function sendIndexRequestWithExpectedHeaders(array $queryParameters, InstitutionUser $actingUser): TestResponse
    {
        return $this
            ->withHeaders(AuthHelpers::createHeadersForInstitutionUser($actingUser))
            ->getJson(
                action(
                    [InstitutionUserController::class, 'index'],
                    $queryParameters
                ),
            );

    }

    public function createExampleQueryParameters($roles, $departments): array
    {
        return [
            'per_page' => 100,
            'sort_by' => 'name',
            'sort_order' => 'asc',
            'roles' => Arr::pluck($roles, 'id'),
            'departments' => Arr::pluck($departments, 'id'),
            'statuses' => Arr::map(InstitutionUserStatus::cases(), fn ($status) => $status->value),
        ];
    }
}
