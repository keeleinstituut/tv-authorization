<?php

namespace Tests\Feature\Routes;

use App\Enums\InstitutionUserStatus;
use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserController;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\EntityHelpers;
use Tests\TestCase;

class InstitutionUserListTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

    public function test_list_of_institution_users_returned(): void
    {
        $institution = $this->createInstitution();
        $institutionUsers = InstitutionUser::factory(10)->for($institution)
            ->has(InstitutionUserRole::factory(2))->create();

        $response = $this->queryInstitutionUsers(
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );

        $response->assertStatus(Response::HTTP_OK);
        foreach ($institutionUsers as $institutionUser) {
            $response->assertJsonFragment(
                $this->buildExpectedResponsePart($institutionUser)
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
        $institutionUsers = InstitutionUser::factory(10)->for($institution)
            ->has(InstitutionUserRole::factory(2))->create();

        $response = $this->queryInstitutionUsers(
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id),
            ['sort_by' => 'name', 'sort_order' => 'asc'],
        );

        $response->assertStatus(Response::HTTP_OK);
        $institutionUsersData = json_decode($response->content(), true);

        $this->assertEquals(
            $institutionUsers->sortBy('user.surname')->pluck('user.surname')->toArray(),
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

        $response = $this->queryInstitutionUsers(
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id),
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
        InstitutionUser::factory(10)->state(new Sequence(
            ['status' => InstitutionUserStatus::Created],
            ['status' => InstitutionUserStatus::Activated],
        ))->for($institution)->has(InstitutionUserRole::factory(2))
            ->create();

        $response = $this->queryInstitutionUsers(
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id),
            ['status' => InstitutionUserStatus::Activated]
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

        $response = $this->queryInstitutionUsers(
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id),
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

    private function getAccessToken(array $privileges, ?string $institutionId = null): string
    {
        $institutionId = $institutionId ?: Str::orderedUuid();

        return $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $institutionId,
            ],
            'privileges' => array_map(fn (PrivilegeKey $key) => $key->value, $privileges),
        ]);
    }

    private function buildExpectedResponsePart(InstitutionUser $institutionUser): array
    {
        return [
            'id' => $institutionUser->id,
            'user' => [
                'id' => $institutionUser->user->id,
                'surname' => $institutionUser->user->surname,
                'forename' => $institutionUser->user->forename,
                'personal_identification_code' => $institutionUser->user->personal_identification_code,
            ],
            'status' => $institutionUser->status,
            'email' => $institutionUser->email,
            'phone' => $institutionUser->phone,
            'roles' => $institutionUser->institutionUserRoles
                ->filter(
                    fn (InstitutionUserRole $institutionUserRole) => is_null($institutionUserRole->deleted_at) &&
                        is_null($institutionUserRole->role->deleted_at)
                )->map(
                    fn (InstitutionUserRole $institutionUserRole) => [
                        'id' => $institutionUserRole->role_id,
                        'name' => $institutionUserRole->role->name,
                    ]
                )->toArray(),
            'created_at' => $institutionUser->created_at->toIsoString(),
            'updated_at' => $institutionUser->updated_at->toIsoString(),
        ];
    }
}
