<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\InstitutionUserSyncController;
use App\Models\InstitutionUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;

class InstitutionUserSyncControllerTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

    public function test_list_of_institution_users_returned(): void
    {
        $institutionUsers = InstitutionUser::factory(5)
            ->create();

        $this->queryInstitutionUsersForSync($this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_OK)
            ->assertJson($this->buildExpectedListResponse($institutionUsers));
    }

    public function test_list_of_institution_users_contains_deleted_items(): void
    {
        $institutionUsers = InstitutionUser::factory(5)->trashed()->create();

        $this->queryInstitutionUsersForSync($this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_OK)
            ->assertJson($this->buildExpectedListResponse($institutionUsers));
    }

    public function test_unauthorized_access_to_list_of_institution_users_returned_401(): void
    {
        $this->queryInstitutionUsersForSync()
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_access_with_incorrect_role_to_list_of_institution_users_returned_403(): void
    {
        $this->queryInstitutionUsersForSync($this->generateServiceAccountAccessToken('wrong-role'))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_single_institution_user_returned(): void
    {
        $institutionUser = InstitutionUser::factory()->create();
        $this->queryInstitutionUserForSync($institutionUser->id, $this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['data' => $this->createInstitutionUserNestedRepresentation($institutionUser)]);
    }

    public function test_single_deleted_institution_user_returned(): void
    {
        $institutionUser = InstitutionUser::factory()->trashed()->create();
        $this->queryInstitutionUserForSync($institutionUser->id, $this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['data' => $this->createInstitutionUserNestedRepresentation($institutionUser)]);
    }

    public function test_receiving_single_institution_user_with_wrong_uuid_value_returned_404(): void
    {
        $this->queryInstitutionUserForSync('some-string', $this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_receiving_single_institution_user_with_not_existing_uuid_value_returned_404(): void
    {
        $this->queryInstitutionUserForSync(Str::orderedUuid(), $this->generateServiceAccountAccessToken())
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    private function buildExpectedListResponse(Collection $institutionUsers): array
    {
        $lastPage = ceil($institutionUsers->count() / InstitutionUserSyncController::PER_PAGE);

        return [
            'data' => $institutionUsers->map(
                fn(InstitutionUser $institutionUser) => $this->createInstitutionUserNestedRepresentation(
                    $institutionUser
                )
            )->toArray(),
            'meta' => [
                'current_page' => 1,
                'last_page' => $lastPage,
                'per_page' => InstitutionUserSyncController::PER_PAGE,
                'total' => $institutionUsers->count(),
            ],
        ];
    }

    private function queryInstitutionUsersForSync(?string $token = null): TestResponse
    {
        if (filled($token)) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ]);
        }

        return $this->getJson(action([InstitutionUserSyncController::class, 'index']));
    }

    private function queryInstitutionUserForSync(string $id, ?string $token = null): TestResponse
    {
        if (filled($token)) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ]);
        }

        return $this->getJson(action([InstitutionUserSyncController::class, 'show'], ['id' => $id]));
    }

    public function generateServiceAccountAccessToken(?string $role = null): string
    {
        $azp = explode(',', config('keycloak.service_accounts_accepted_authorized_parties'))[0];

        return AuthHelpers::createJwt([
            'iss' => config('keycloak.base_url') . '/realms/' . config('keycloak.realm'),
            'azp' => $azp,
            'realm_access' => [
                'roles' => filled($role) ? [$role] : [config('keycloak.service_account_sync_role')],
            ],
        ]);
    }

    private function createInstitutionUserNestedRepresentation(InstitutionUser $institutionUser): array
    {
        return [
            ...RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser),
            'deleted_at' => $institutionUser->deleted_at?->toISOString()
        ];
    }
}
