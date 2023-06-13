<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserImportController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
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

        $row = [
            'personal_identification_code' => '39511267470',
            'name' => 'user name',
            'email' => 'some@email.com',
            'phone' => '+372 56789566',
            'department' => $department->name,
            'role' => $role->name,
        ];

        $this->sendValidationRequest($row, AuthHelpers::generateAccessTokenForInstitutionUser(
            $this->createInstitutionUserWithRoles(
                $institution,
                $this->createRoleWithPrivileges($institution, [PrivilegeKey::AddUser])
            )
        ))->assertOk();
    }

    public function test_row_with_already_existing_user_returned_200(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $existingInstitutionUser = $this->createInstitutionUserWithRoles($institution, $role);
        $existingUser = $existingInstitutionUser->user;

        $row = [
            'personal_identification_code' => $existingUser->personal_identification_code,
            'name' => $existingUser->forename.' '.$existingUser->surname,
            'email' => $existingInstitutionUser->email,
            'phone' => $existingInstitutionUser->phone,
            'department' => '',
            'role' => $role->name,
        ];

        $this->sendValidationRequest($row, AuthHelpers::generateAccessTokenForInstitutionUser(
            $this->createInstitutionUserWithRoles(
                $institution,
                $this->createRoleWithPrivileges($institution, [PrivilegeKey::AddUser])
            )
        ))->assertOk()->assertJson([
            'isExistingInstitutionUser' => true,
        ]);
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

        $institution = $this->createInstitution();
        $this->sendValidationRequest(
            $row, AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->createInstitutionUserWithRoles(
                    $institution,
                    $this->createRoleWithPrivileges($institution, [PrivilegeKey::AddUser])
                ))
        )->assertUnprocessable()
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
            ->assertUnauthorized();
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
