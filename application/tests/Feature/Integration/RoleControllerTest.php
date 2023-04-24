<?php

namespace Tests\Feature\Integration;

use App\Models\Role;
use App\Models\Institution;
use App\Models\PrivilegeRole;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private $testNow;

    public function setUp(): void {
        parent::setup();

        $this->testNow = Carbon::create(2001, 5, 21, 12);
        Carbon::setTestNow($this->testNow);
    }

    /**
     * A basic feature test example.
     */
    public function test_api_roles_list_endpoint(): void
    {
        $role = Role::factory()->create();
        PrivilegeRole::factory(3)->create([
            'role_id' => $role->id,
        ]);
        $role->load('privileges');

        $accessToken = $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $role->institution_id,
            ],
            'privileges' => [
                'VIEW_ROLE',
            ]
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->getJson("/api/roles?institution_id=$role->institution_id");

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [
                    $this->constructRoleRepresentation($role),
                ]
            ]);
    }

    public function test_api_roles_single_endpoint(): void
    {
        $role = Role::factory()->create();
        PrivilegeRole::factory(3)->create([
            'role_id' => $role->id,
        ]);
        $role->load('privileges');

        $accessToken = $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $role->institution_id,
            ],
            'privileges' => [
                'VIEW_ROLE',
            ]
        ]);

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
        $accessToken = $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $institution->id,
            ],
            'privileges' => [
                'ADD_ROLE',
            ]
        ]);

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
        ])->postJson("/api/roles", $payload);

        $savedRole = Role::first();

        $response
            ->assertStatus(201)
            ->assertJson([
                'data' => $this->constructRoleRepresentation($savedRole),
            ]);
    }

    public function test_api_roles_update_endpoint(): void
    {
        $role = Role::factory()->create();

        $accessToken = $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $role->institution_id,
            ],
            'privileges' => [
                'EDIT_ROLE',
            ]
        ]);

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

    public function test_api_roles_delete_endpoint(): void
    {
        $role = Role::factory()->create();

        $accessToken = $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $role->institution_id,
            ],
            'privileges' => [
                'DELETE_ROLE',
            ]
        ]);

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


    public function test_unauthorized_institution(): void {
        // Populate some roles
        $roles = Role::factory(3)->create();

        $accessToken = $this->generateAccessToken([
            'selectedInstitution' => [
                // Some non existing institution id
                'id' => Str::orderedUuid(),
            ],
            'privileges' => [
                'VIEW_ROLE',
                'ADD_ROLE',
                'EDIT_ROLE',
                'DELETE_ROLE',
            ]
        ]);

        $roleId = $roles[0]->id;

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->getJson("/api/roles")
            ->assertStatus(403);

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->postJson("/api/roles")
            ->assertStatus(403);

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->getJson("/api/roles/$roleId")
            ->assertStatus(403);

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->putJson("/api/roles/$roleId")
            ->assertStatus(403);

        $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ])
            ->deleteJson("/api/roles/$roleId")
            ->assertStatus(403);
    }


    private function constructRoleRepresentation(Role $role) {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'institution_id' => $role->institution_id,
            'privileges' => collect($role->privileges)->map(fn ($privilege) => [
                "key" => $privilege->key->value,
            ])->toArray(),
            'created_at' => $role->created_at->toIsoString(),
            'updated_at' => $role->updated_at->toIsoString(),
        ];
    }

}
