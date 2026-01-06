<?php

namespace Tests\Feature\Integration;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\Privilege;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\TestCase;

class PrivilegeControllerTest extends TestCase
{
    use InstitutionUserHelpers, RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_api_privileges_endpoint(): void
    {
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege(Institution::factory(), PrivilegeKey::ViewRole);
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->getJson('/api/privileges');

        $privileges = collect(Privilege::orderBy('key')->get())
            ->map(fn ($privilege) => $this->constructPrivilegeRepresentation($privilege))
            ->toArray();

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => $privileges,
            ]);
    }

    public function test_unauthorized(): void
    {
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege(Institution::factory(), null);
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->getJson('/api/privileges');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    private function constructPrivilegeRepresentation(Privilege $privilege)
    {
        return [
            'key' => $privilege->key->value,
        ];
    }
}
