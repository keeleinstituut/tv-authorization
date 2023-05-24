<?php

namespace Tests\Feature\Routes;

use App\Enums\InstitutionUserStatus;
use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserController;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Privilege;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\EntityHelpers;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;

class InstitutionUserListTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

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
        $accessToken = self::generateAccessToken(self::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

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
        $accessToken = self::generateAccessToken(self::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

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
        $accessToken = self::generateAccessToken(self::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

        $response = $this->queryInstitutionUsers(
            $accessToken,
            ['role_id' => $role->id],
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
        $accessToken = self::generateAccessToken(self::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

        $response = $this->queryInstitutionUsers(
            $accessToken,
            ['status' => InstitutionUserStatus::Active]
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
        $accessToken = self::generateAccessToken(self::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

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
}