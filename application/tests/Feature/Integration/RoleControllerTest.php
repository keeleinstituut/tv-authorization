<?php

namespace Tests\Feature\Integration;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use App\Models\User;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Tests\Assertions;
use Tests\AuditLogTestCase;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;

class RoleControllerTest extends AuditLogTestCase
{
    use InstitutionUserHelpers, RefreshDatabase;

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
            'X-Request-Id' => static::TRACE_ID,
        ])->postJson('/api/roles', $payload);

        $savedRole = Role::findOrFail($response->json('data.id'));

        $response
            ->assertStatus(201)
            ->assertJson([
                'data' => $this->constructRoleRepresentation($savedRole),
            ]);

        $this->assertMessageRepresentsRoleCreation(
            $this->retrieveLatestAuditLogMessageBody(),
            $actingUser,
            function (array $actualEventParameters) use ($institution, $savedRole) {
                $expectedEventParametersSubset = [
                    'object_identity_subset' => $savedRole->getIdentitySubset(),
                    'object_data' => $savedRole->getAuditLogRepresentation(),
                ];
                Assertions::assertArraysEqualIgnoringOrder(
                    $expectedEventParametersSubset,
                    collect($actualEventParameters)->intersectByKeys($expectedEventParametersSubset)->all()
                );
                $expectedObjectDataSubset = [
                    'name' => 'Test Role',
                    'privileges' => data_get($savedRole->getAuditLogRepresentation(), 'privileges'),
                    'institution_id' => $institution->id,
                ];
                Assertions::assertArraysEqualIgnoringOrder(
                    $expectedObjectDataSubset,
                    collect($actualEventParameters['object_data'])->intersectByKeys($expectedObjectDataSubset)->all()
                );
            }
        );
    }

    public function test_api_roles_update_endpoint(): void
    {
        $role = Role::factory()
            ->for($institution = Institution::factory()->create())
            ->has(PrivilegeRole::factory(3))
            ->create();

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

        $roleBeforeRequest = $role->getAuditLogRepresentation();

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
            'X-Request-Id' => static::TRACE_ID,
        ])->putJson("/api/roles/$role->id", $payload);

        $savedRole = Role::find($role->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => $this->constructRoleRepresentation($savedRole),
            ]);

        $this->assertMessageRepresentsRoleModification(
            $this->retrieveLatestAuditLogMessageBody(),
            $roleBeforeRequest,
            $actingUser,
            function (array $actualEventParameters) use ($savedRole, $roleBeforeRequest) {
                $expectedEventParametersSubset = [
                    'pre_modification_subset' => [
                        'name' => $roleBeforeRequest['name'],
                        'privileges' => data_get($roleBeforeRequest, 'privileges'),
                    ],
                    'post_modification_subset' => [
                        'name' => 'Test Role',
                        'privileges' => data_get($savedRole->getAuditLogRepresentation(), 'privileges'),
                    ],
                ];
                $this->assertArraysEqualIgnoringOrder(
                    $expectedEventParametersSubset,
                    collect($actualEventParameters)->intersectByKeys($expectedEventParametersSubset)->all()
                );
            }
        );
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
            'X-Request-Id' => static::TRACE_ID,
        ])->deleteJson("/api/roles/$role->id");

        $savedRole = Role::find($role->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => $this->constructRoleRepresentation($role),
            ]);

        $this->assertNull($savedRole);

        $this->assertMessageRepresentsRoleRemoval($this->retrieveLatestAuditLogMessageBody(), $actingUser, $role);
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
            'is_root' => $role->is_root,
        ];
    }

    /**
     * @param  Closure(array): void  $assertOnEventParameters
     */
    private function assertMessageRepresentsRoleModification(array $actualMessageBody, array $roleBeforeRequest, InstitutionUser $actingUser, Closure $assertOnEventParameters): void
    {
        $expectedMessageBodySubset = [
            'event_type' => AuditLogEventType::ModifyObject->value,
            'happened_at' => Date::getTestNow()->toISOString(),
            'trace_id' => static::TRACE_ID,
            'failure_type' => null,
            'context_institution_id' => $actingUser->institution_id,
            'context_department_id' => $actingUser->department_id,
            'acting_institution_user_id' => $actingUser->id,
            'acting_user_pic' => $actingUser->user->personal_identification_code,
            'acting_user_forename' => $actingUser->user->forename,
            'acting_user_surname' => $actingUser->user->surname,
        ];

        Assertions::assertArrayHasSubsetIgnoringOrder(
            $expectedMessageBodySubset,
            collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
        );

        $eventParameters = data_get($actualMessageBody, 'event_parameters');
        $this->assertIsArray($eventParameters);

        $expectedEventParametersSubset = [
            'object_type' => AuditLogEventObjectType::Role->value,
            'object_identity_subset' => Arr::only($roleBeforeRequest, ['id', 'name']),
        ];
        Assertions::assertArraysEqualIgnoringOrder(
            $expectedEventParametersSubset,
            collect($eventParameters)->intersectByKeys($expectedEventParametersSubset)->all(),
        );

        $assertOnEventParameters($eventParameters);
    }

    /**
     * @param  Closure(array): void  $assertOnEventParameters
     */
    private function assertMessageRepresentsRoleCreation(array $actualMessageBody, InstitutionUser $actingUser, Closure $assertOnEventParameters): void
    {
        $expectedMessageBodySubset = [
            'event_type' => AuditLogEventType::CreateObject->value,
            'happened_at' => Date::getTestNow()->toISOString(),
            'trace_id' => static::TRACE_ID,
            'failure_type' => null,
            'context_institution_id' => $actingUser->institution_id,
            'context_department_id' => $actingUser->department_id,
            'acting_institution_user_id' => $actingUser->id,
            'acting_user_pic' => $actingUser->user->personal_identification_code,
            'acting_user_forename' => $actingUser->user->forename,
            'acting_user_surname' => $actingUser->user->surname,
        ];

        Assertions::assertArrayHasSubsetIgnoringOrder(
            $expectedMessageBodySubset,
            collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
        );

        $eventParameters = data_get($actualMessageBody, 'event_parameters');
        $this->assertIsArray($eventParameters);

        $expectedEventParametersSubset = [
            'object_type' => AuditLogEventObjectType::Role->value,
        ];
        Assertions::assertArraysEqualIgnoringOrder(
            $expectedEventParametersSubset,
            collect($eventParameters)->intersectByKeys($expectedEventParametersSubset)->all(),
        );

        $assertOnEventParameters($eventParameters);
    }

    private function assertMessageRepresentsRoleRemoval(array $actualMessageBody, InstitutionUser $actingUser, Role $role): void
    {
        $expectedMessageBodySubset = [
            'event_type' => AuditLogEventType::RemoveObject->value,
            'happened_at' => Date::getTestNow()->toISOString(),
            'trace_id' => static::TRACE_ID,
            'failure_type' => null,
            'context_institution_id' => $actingUser->institution_id,
            'context_department_id' => $actingUser->department_id,
            'acting_institution_user_id' => $actingUser->id,
            'acting_user_pic' => $actingUser->user->personal_identification_code,
            'acting_user_forename' => $actingUser->user->forename,
            'acting_user_surname' => $actingUser->user->surname,
        ];

        Assertions::assertArrayHasSubsetIgnoringOrder(
            $expectedMessageBodySubset,
            collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
        );

        $eventParameters = data_get($actualMessageBody, 'event_parameters');
        $this->assertIsArray($eventParameters);

        $expectedEventParametersSubset = [
            'object_type' => AuditLogEventObjectType::Role->value,
            'object_identity_subset' => $role->getIdentitySubset(),
        ];
        Assertions::assertArraysEqualIgnoringOrder(
            $expectedEventParametersSubset,
            collect($eventParameters)->intersectByKeys($expectedEventParametersSubset)->all(),
        );
    }
}
