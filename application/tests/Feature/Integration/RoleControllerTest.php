<?php

namespace Tests\Feature\Integration;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase, InstitutionUserHelpers;

    private $testNow;

    public function setUp(): void
    {
        parent::setup();

        $this->testNow = Carbon::create(2001, 5, 21, 12);
        Carbon::setTestNow($this->testNow);
    }

    /**
     * A basic feature test example.
     */
    public function test_api_roles_list_endpoint(): void
    {
        $role = Role::factory()->for(
            $institution = Institution::factory()->create()
        )->create();
        PrivilegeRole::factory(3)->create([
            'role_id' => $role->id,
        ]);
        $role->load('privileges');

        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($institution, PrivilegeKey::ViewRole);
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->getJson("/api/roles?institution_id=$role->institution_id");

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [
                    $this->constructRoleRepresentation($role),
                ],
            ]);
    }

    public function test_api_roles_single_endpoint(): void
    {
        $role = Role::factory()->for(
            $institution = Institution::factory()->create()
        )->create();
        PrivilegeRole::factory(3)->create([
            'role_id' => $role->id,
        ]);
        $role->load('privileges');

        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($institution, PrivilegeKey::ViewRole);
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->getJson("/api/roles/$role->id");

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => $this->constructRoleRepresentation($role),
            ]);
    }

    public function test_api_roles_create_endpoint(): void
    {
        $institution = Institution::factory()->create();
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($institution, PrivilegeKey::AddRole);
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser);

        $payload = json_decode(<<<EOT
            {
                "name": "Test Role",
                "institution_id": "$institution->id",
                "privileges": [
                    "VIEW_ROLE"
                ]
            }
        EOT, true);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->postJson('/api/roles', $payload);

        $savedRole = Role::findOrFail($response->json('data.id'));

        $response
            ->assertStatus(201)
            ->assertJson([
                'data' => $this->constructRoleRepresentation($savedRole),
            ]);
    }

    public function test_api_roles_update_endpoint(): void
    {
        $role = Role::factory()->for(
            $institution = Institution::factory()->create()
        )->create();
        PrivilegeRole::factory(3)->create([
            'role_id' => $role->id,
        ]);
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($institution, PrivilegeKey::EditRole);
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser);

        $payload = json_decode(<<<EOT
            {
                "name": "Test Role",
                "institution_id": "$role->institution_id",
                "privileges": [
                    "VIEW_ROLE"
                ]
            }
        EOT, true);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->putJson("/api/roles/$role->id", $payload);

        $savedRole = Role::find($role->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => $this->constructRoleRepresentation($savedRole),
            ]);
    }

    public function test_api_roles_update_endpoint_removing_and_adding_privilege(): void
    {
        $role = Role::factory()->for(
            $institution = Institution::factory()->create()
        )->create();
        PrivilegeRole::factory()->create([
            'role_id' => $role->id,
            'privilege_id' => Privilege::where('key', 'ADD_ROLE')->first()->id,
        ]);
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($institution, PrivilegeKey::EditRole);
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser);

        $response = $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->putJson("/api/roles/$role->id", json_decode(<<<EOT
                {
                    "name": "Test Role",
                    "institution_id": "$role->institution_id",
                    "privileges": [
                        "VIEW_ROLE"
                    ]
                }
                EOT, true)
            );
        $response->assertStatus(200);

        $response = $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->putJson("/api/roles/$role->id", json_decode(<<<EOT
                {
                    "name": "Test Role",
                    "institution_id": "$role->institution_id",
                    "privileges": [
                        "ADD_ROLE"
                    ]
                }
                EOT, true)
            );

        $savedRole = Role::find($role->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => $this->constructRoleRepresentation($savedRole),
            ]);
    }

    public function test_api_roles_delete_endpoint(): void
    {
        $role = Role::factory()->for(
            $institution = Institution::factory()->create()
        )->create();

        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($institution, PrivilegeKey::DeleteRole);
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->deleteJson("/api/roles/$role->id");

        $savedRole = Role::find($role->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => $this->constructRoleRepresentation($role),
            ]);

        $this->assertNull($savedRole);
    }

    public function test_unauthorized_institution(): void
    {
        // Populate some roles
        $roles = Role::factory(3)->create();

        $actingUser = InstitutionUser::factory()
            ->for(Institution::factory())
            ->for(User::factory())
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser(
            $actingUser,
            ['privileges' => [
                PrivilegeKey::ViewRole->value,
                PrivilegeKey::AddRole->value,
                PrivilegeKey::EditRole->value,
                PrivilegeKey::DeleteRole->value,
            ],
            ]);

        $role = $roles[0];
        $roleId = $role->id;

        // Returns empty array since no roles exist in institution
        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->getJson('/api/roles')
            ->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->postJson('/api/roles', json_decode(<<<EOT
                {
                    "name": "Test Role",
                    "institution_id": "$role->institution_id",
                    "privileges": [
                        "ADD_ROLE"
                    ]
                }
                EOT, true)
            )
            ->assertStatus(403);

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->getJson("/api/roles/$roleId")
            ->assertStatus(404);

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->putJson("/api/roles/$roleId", json_decode(<<<EOT
                {
                    "name": "Test Role",
                    "institution_id": "$role->institution_id",
                    "privileges": [
                        "ADD_ROLE",
                        "VIEW_ROLE",
                        "EDIT_ROLE"
                    ]
                }
                EOT, true)
            )
            ->assertStatus(404);

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->deleteJson("/api/roles/$roleId")
            ->assertStatus(404);
    }

    private function constructRoleRepresentation(Role $role)
    {
        $role->load('privilegeRoles.privilege');

        return [
            'id' => $role->id,
            'name' => $role->name,
            'institution_id' => $role->institution_id,
            'privileges' => collect($role->privilegeRoles)
                ->filter(fn ($privilegeRole) => $privilegeRole->deleted_at == null && $privilegeRole->privilege->deleted_at == null)
                ->map(fn ($privilegeRole) => [
                    'key' => $privilegeRole->privilege->key->value,
                ])->toArray(),
            'created_at' => $role->created_at->toIsoString(),
            'updated_at' => $role->updated_at->toIsoString(),
        ];
    }
}
