<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserImportController;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\TestCase;

class InstitutionUserImportControllerValidateCsvRowTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

    public function test_valid_row_returned_200(): void
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();

        $row = [
            'personal_identification_code' => '39511267470',
            'name' => 'user name',
            'email' => 'some@email.com',
            'phone' => '+372 56789566',
            'department' => $department->name,
            'role' => $role->name,
        ];

        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);
        $this->sendValidationRequest($row, $accessToken)
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_invalid_row_returned_422(): void
    {
        $row = [
            'personal_identification_code' => '39511267471',
            'name' => 'user name 234',
            'email' => 'someemail.com',
            'phone' => '+372 567895',
            'department' => 'wrong department',
            'role' => 'wrong_role',
        ];

        $actingInstitutionUser = InstitutionUser::factory()
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();

        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendValidationRequest($row, $accessToken)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'errors' => [
                    'personal_identification_code' => [],
                    'name' => [],
                    'phone' => [],
                    'email' => [],
                    'department' => [],
                ],
            ]);
    }

    public function test_unauthorized_request_returned_403(): void
    {
        $this->sendValidationRequest([])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    private function sendValidationRequest(array $row, string $accessToken = ''): TestResponse
    {
        if (! empty($accessToken)) {
            $this->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ]);
        }

        return $this->postJson(
            action([InstitutionUserImportController::class, 'validateCsvRow']),
            $row
        );
    }
}
