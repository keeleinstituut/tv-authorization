<?php

namespace Tests\Feature\Integration;

use App\Models\Privilege;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthHelpers;
use Tests\TestCase;

class PrivilegeControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_api_privileges_endpoint(): void
    {
        $accessToken = AuthHelpers::generateAccessToken([
            'privileges' => [
                'VIEW_ROLE',
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->getJson('/api/privileges');

        $privileges = collect(Privilege::all())
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
        $accessToken = AuthHelpers::generateAccessToken([
            'privileges' => [
            ],
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Accept' => 'application/json',
        ])->getJson('/api/privileges');

        $response->assertStatus(403);
    }

    private function constructPrivilegeRepresentation(Privilege $privilege)
    {
        return [
            'key' => $privilege->key->value,
        ];
    }
}
