<?php

namespace Tests\Feature\Routes;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\JwtClaimsController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\TestCase;
use Throwable;

class JwtClaimsTest extends TestCase
{
    use EntityHelpers, RefreshDatabase;

    const PRIVILEGES_A = [PrivilegeKey::AddUser];

    const PRIVILEGES_B = [PrivilegeKey::DeleteRole, PrivilegeKey::DeactivateUser];

    public function test_correct_claims_returned_when_institution_not_selected(): void
    {
        $user = User::factory()->create();

        $this->queryJwtClaims($user->personal_identification_code)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson($this->buildExpectedResponseWhenInstitutionNotSelected($user));
    }

    public function test_correct_claims_returned_for_user_with_single_role_when_institution_selected(): void
    {
        $institution = $this->createInstitution();
        $institutionUser = $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges($institution, self::PRIVILEGES_A)
        );

        $this->queryJwtClaims(
            $institutionUser->user->personal_identification_code,
            $institution->id
        )->assertStatus(Response::HTTP_OK)
            ->assertExactJson($this->buildExpectedResponseWhenInstitutionSelected($institutionUser, self::PRIVILEGES_A));
    }

    public function test_correct_claims_returned_for_user_with_two_roles_when_institution_selected(): void
    {
        $institution = $this->createInstitution();
        $institutionUser = $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges($institution, self::PRIVILEGES_A),
            $this->createRoleWithPrivileges($institution, self::PRIVILEGES_B)
        );

        $this->queryJwtClaims(
            $institutionUser->user->personal_identification_code,
            $institution->id
        )->assertStatus(Response::HTTP_OK)
            ->assertExactJson($this->buildExpectedResponseWhenInstitutionSelected(
                $institutionUser,
                array_merge(self::PRIVILEGES_A, self::PRIVILEGES_B)
            ));
    }

    /**
     * @throws Throwable
     */
    public function test_correct_claims_returned_after_deleting_one_role_when_institution_selected(): void
    {
        $institution = $this->createInstitution();
        $roleA = $this->createRoleWithPrivileges($institution, self::PRIVILEGES_A);
        $roleB = $this->createRoleWithPrivileges($institution, self::PRIVILEGES_B);
        $institutionUser = $this->createInstitutionUserWithRoles($institution, $roleA, $roleB);

        $roleA->deleteOrFail();

        $this->queryJwtClaims(
            $institutionUser->user->personal_identification_code,
            $institution->id
        )->assertStatus(Response::HTTP_OK)
            ->assertExactJson($this->buildExpectedResponseWhenInstitutionSelected(
                $institutionUser,
                self::PRIVILEGES_B
            ));
    }

    public function test_no_privileges_returned_after_deleting_privilege_roles_when_institution_selected(): void
    {
        $institution = $this->createInstitution();
        $roleA = $this->createRoleWithPrivileges($institution, self::PRIVILEGES_A);
        $institutionUser = $this->createInstitutionUserWithRoles($institution, $roleA);

        $roleA->privilegeRoles->each->deleteOrFail();

        $this->queryJwtClaims(
            $institutionUser->user->personal_identification_code,
            $institution->id
        )->assertStatus(Response::HTTP_OK)
            ->assertExactJson($this->buildExpectedResponseWhenInstitutionSelected($institutionUser, []));
    }

    public function test_request_with_invalid_azp_claim_in_token_returns_403(): void
    {
        $institution = $this->createInstitution();
        $institutionUser = $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges($institution, self::PRIVILEGES_A)
        );

        $normallyAcceptedAzp = Str::of(config('keycloak.accepted_authorized_parties'))
            ->explode(',')
            ->firstOrFail();
        $this->assertNotEquals($normallyAcceptedAzp, config('api.sso_internal_client_id'));

        $accessToken = AuthHelpers::generateAccessToken(azp: $normallyAcceptedAzp);

        $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
        ])->getJson(action(
            [JwtClaimsController::class, 'show'],
            [
                'personal_identification_code' => $institutionUser->user->personal_identification_code,
                'institution_id' => $institution->id,
            ]
        ))->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_request_with_missing_azp_claim_in_token_returns_403(): void
    {
        $institution = $this->createInstitution();
        $institutionUser = $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges($institution, self::PRIVILEGES_A)
        );

        $accessToken = AuthHelpers::generateAccessToken();

        $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
        ])->getJson(action(
            [JwtClaimsController::class, 'show'],
            [
                'personal_identification_code' => $institutionUser->user->personal_identification_code,
                'institution_id' => $institution->id,
            ]
        ))->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_request_with_missing_token_returns_401(): void
    {
        $institution = $this->createInstitution();
        $institutionUser = $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges($institution, self::PRIVILEGES_A)
        );

        $this->getJson(action(
            [JwtClaimsController::class, 'show'],
            [
                'personal_identification_code' => $institutionUser->user->personal_identification_code,
                'institution_id' => $institution->id,
            ]
        ))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_request_with_nonexistent_pic_returns_404(): void
    {
        $this->queryJwtClaims('47607239590', Str::uuid())->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @throws Throwable
     */
    public function test_404_returned_after_deleting_institution_user(): void
    {
        $institution = $this->createInstitution();
        $institutionUser = $this->createInstitutionUserWithRoles($institution);
        $personalIdentificationCode = $institutionUser->user->personal_identification_code;

        $institutionUser->deleteOrFail();

        $this->queryJwtClaims(
            $personalIdentificationCode,
            $institution->id
        )->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @throws Throwable
     */
    public function test_404_returned_after_deleting_institution(): void
    {
        $institution = $this->createInstitution();
        $institutionId = $institution->id;
        $institutionUser = $this->createInstitutionUserWithRoles($institution);

        $institution->deleteOrFail();

        $this->queryJwtClaims($institutionUser->user->personal_identification_code, $institutionId)
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_404_returned_when_user_does_not_belong_to_selected_institution(): void
    {
        $this->queryJwtClaims(
            User::factory()->create()->personal_identification_code,
            Institution::factory()->create()->id
        )->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_request_with_empty_pic_returns_422(): void
    {
        $this->queryJwtClaims('', Str::uuid())->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_request_with_empty_institution_id_returns_422(): void
    {
        $this->queryJwtClaims('47607239590', '')->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_request_with_invalid_institution_id_returns_422(): void
    {
        $this->queryJwtClaims('47607239590', 'not-uuid')->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function queryJwtClaims(?string $pic, ?string $institutionId = null): TestResponse
    {
        $accessToken = $this->generateInternalClientAccessToken();
        $parameters = $institutionId === null
            ? ['personal_identification_code' => $pic]
            : ['personal_identification_code' => $pic, 'institution_id' => $institutionId];

        return $this->withHeaders([
            'Authorization' => "Bearer $accessToken",
        ])->getJson(action(
            [JwtClaimsController::class, 'show'],
            $parameters
        ));
    }

    /**
     * @param  array<PrivilegeKey>  $expectedPrivileges
     */
    public function buildExpectedResponseWhenInstitutionSelected(InstitutionUser $expectedInstitutionUser, array $expectedPrivileges): array
    {
        return [
            'personalIdentificationCode' => $expectedInstitutionUser->user->personal_identification_code,
            'userId' => $expectedInstitutionUser->user->id,
            'institutionUserId' => $expectedInstitutionUser->id,
            'forename' => $expectedInstitutionUser->user->forename,
            'surname' => $expectedInstitutionUser->user->surname,
            'selectedInstitution' => [
                'id' => $expectedInstitutionUser->institution->id,
                'name' => $expectedInstitutionUser->institution->name,
            ],
            'department' => [
                'id' => $expectedInstitutionUser->department?->id,
                'name' => $expectedInstitutionUser->department?->name,
            ],
            'privileges' => collect($expectedPrivileges)
                ->map(fn (PrivilegeKey $privilege) => $privilege->value)
                ->unique()
                ->toArray(),
        ];
    }

    public function buildExpectedResponseWhenInstitutionNotSelected(User $expectedUser): array
    {
        return [
            'personalIdentificationCode' => $expectedUser->personal_identification_code,
            'userId' => $expectedUser->id,
            'forename' => $expectedUser->forename,
            'surname' => $expectedUser->surname,
        ];
    }

    public function generateInternalClientAccessToken(): string
    {
        return AuthHelpers::generateAccessToken(azp: config('api.sso_internal_client_id'));
    }
}
