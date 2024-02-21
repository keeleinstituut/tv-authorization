<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\InstitutionSyncController;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\TestCase;

class InstitutionSyncControllerTest extends TestCase
{
    use EntityHelpers, RefreshDatabase;

    public function test_list_of_institutions_returned(): void
    {
        $institutions = Institution::factory(5)
            ->create();

        $this->queryInstitutionsForSync($this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_OK)
            ->assertJson($this->buildExpectedListResponse($institutions));
    }

    public function test_list_of_institutions_with_deleted_returned(): void
    {
        $institutions = Institution::factory(5)->trashed()->create();

        $this->queryInstitutionsForSync($this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_OK)
            ->assertJson($this->buildExpectedListResponse($institutions));
    }

    public function test_unauthorized_access_to_list_of_institutions_returned_401(): void
    {
        $this->queryInstitutionsForSync()
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_access_with_incorrect_role_to_list_of_institutions_returned_403(): void
    {
        $this->queryInstitutionsForSync($this->generateServiceAccountAccessToken('wrong-role'))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_single_institution_returned(): void
    {
        $institution = Institution::factory()->create();
        $this->queryInstitutionForSync($institution->id, $this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['data' => $this->createInstitutionRepresentation($institution)]);
    }

    public function test_single_deleted_institution_returned(): void
    {
        $institution = Institution::factory()->trashed()->create();
        $this->queryInstitutionForSync($institution->id, $this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['data' => $this->createInstitutionRepresentation($institution)]);
    }

    public function test_receiving_single_institution_user_with_wrong_uuid_value_returned_404(): void
    {
        $this->queryInstitutionForSync('some-string', $this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_receiving_single_institution_user_with_not_existing_uuid_value_returned_404(): void
    {
        $this->queryInstitutionForSync(Str::orderedUuid(), $this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    private function buildExpectedListResponse(Collection $institutions): array
    {
        return [
            'data' => $institutions->map(
                fn (Institution $institution) => $this->createInstitutionRepresentation($institution)
            )->toArray(),
        ];
    }

    private function createInstitutionRepresentation(Institution $institution): array
    {
        return [
            'id' => $institution->id,
            'name' => $institution->name,
            'short_name' => $institution->short_name,
            'phone' => $institution->phone,
            'email' => $institution->email,
            'logo_url' => $institution->logo_url,
            'deleted_at' => $institution->deleted_at?->toISOString(),
        ];
    }

    private function queryInstitutionsForSync(string $token = null): TestResponse
    {
        if (filled($token)) {
            $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ]);
        }

        return $this->getJson(action([InstitutionSyncController::class, 'index']));
    }

    private function queryInstitutionForSync(string $id, string $token = null): TestResponse
    {
        if (filled($token)) {
            $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ]);
        }

        return $this->getJson(action([InstitutionSyncController::class, 'show'], ['id' => $id]));
    }

    public function generateServiceAccountAccessToken(string $role = null): string
    {
        $azp = explode(',', config('keycloak.service_accounts_accepted_authorized_parties'))[0];

        return AuthHelpers::createJwt([
            'iss' => config('keycloak.base_url').'/realms/'.config('keycloak.realm'),
            'azp' => $azp,
            'realm_access' => [
                'roles' => filled($role) ? [$role] : [config('keycloak.service_account_sync_role')],
            ],
        ]);
    }
}
