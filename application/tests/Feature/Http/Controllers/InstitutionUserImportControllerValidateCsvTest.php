<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserImportController;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\TestCase;

class InstitutionUserImportControllerValidateCsvTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

    public function test_validation_with_correct_csv_file_returned_200(): void
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
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($role->name, $department->name),
            ]),
            $accessToken
        )->assertOk()
            ->assertExactJson(['errors' => []]);
    }

    public function test_validation_without_auth_token_returned_403()
    {
        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_validation_without_corresponding_privilege_returned_403()
    {
        $actingInstitutionUser = InstitutionUser::factory()
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::ActivateUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
            $accessToken
        )->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_validation_with_file_that_has_invalid_headers_returned_400()
    {
        $institution = $this->createInstitution();
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
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                ['Roll', 'Isikukood', 'Nimi', 'Meiliaadress', 'Telefoninumber', 'Üksus'],
                $this->getValidCsvRow($role->name),
            ]),
            $accessToken
        )->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_validation_with_file_that_has_wrong_columns_count_returned_400()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $invalidCsvRow = $this->getValidCsvRow($role->name);
        $invalidCsvRow[] = 'some value';

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $invalidCsvRow,
            ]),
            $accessToken
        )->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_validation_with_file_that_has_wrong_email_column_value_stored_errors()
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name, $department->name);
        $csvRow[2] = 'wrongemail.com';

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $accessToken
        )->assertUnprocessable()
            ->assertJson([
                'errors' => [
                    [
                        'row' => 0,
                        'errors' => [
                            'email' => [],
                        ],
                    ],
                ],
            ]);
    }

    public function test_validation_with_file_that_has_wrong_phone_column_value_stored_errors()
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name, $department->name);
        $csvRow[3] = '1234455678899';

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $accessToken
        )->assertUnprocessable()
            ->assertJson([
                'errors' => [
                    [
                        'row' => 0,
                        'errors' => [
                            'phone' => [],
                        ],
                    ],
                ],
            ]);
    }

    public function test_validation_with_file_that_has_wrong_name_column_value_stored_errors()
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name, $department->name);
        $csvRow[1] = 'Wrong Username23';

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $accessToken
        )->assertUnprocessable()
            ->assertJson([
                'errors' => [
                    [
                        'row' => 0,
                        'errors' => [
                            'name' => [],
                        ],
                    ],
                ],
            ]);
    }

    public function test_validation_with_file_that_has_wrong_role_column_value_stored_errors()
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $csvRow = $this->getValidCsvRow('role-name', $department->name);

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $accessToken
        )->assertUnprocessable()
            ->assertJson([
                'errors' => [
                    [
                        'row' => 0,
                        'errors' => [
                            'role' => [],
                        ],
                    ],
                ],
            ]);
    }

    public function test_validation_with_file_that_has_wrong_department_column_value_stored_errors()
    {
        $institution = $this->createInstitution();
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
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $csvRow = $this->getValidCsvRow($role->name, 'wrong-department');
        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $accessToken
        )->assertUnprocessable()
            ->assertJson([
                'errors' => [
                    [
                        'row' => 0,
                        'errors' => [
                            'department' => [],
                        ],
                    ],
                ],
            ]);
    }

    public function test_validation_with_file_that_has_multiple_roles_value()
    {
        $institution = $this->createInstitution();

        $role1 = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);
        $role2 = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $roleColumnValue = implode(', ', [$role1->name, $role2->name]);
        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($roleColumnValue),
            ]),
            $accessToken
        )->assertOk();
    }

    public function test_validation_with_file_that_has_empty_role_value()
    {
        $institution = $this->createInstitution();

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow(','),
            ]),
            $accessToken
        )->assertUnprocessable();
    }

    public function test_validation_with_file_that_has_role_from_another_institution_returned_422()
    {
        $roleFromAnotherInstitution = $this->createRoleWithPrivileges($this->createInstitution(), [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $institution = $this->createInstitution();

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser);

        $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($roleFromAnotherInstitution->name),
            ]),
            $accessToken
        )->assertUnprocessable()
            ->assertJson([
                'errors' => [
                    [
                        'row' => 0,
                        'errors' => [
                            'role' => [],
                        ],
                    ],
                ],
            ]);
    }

    private function composeContent($rows): string
    {
        $content = '';
        foreach ($rows as $row) {
            $content .= implode(';', $row).PHP_EOL;
        }

        return $content;
    }

    private function sendImportFileValidationRequest(string $fileContent, string $accessToken = ''): TestResponse
    {
        if (! empty($accessToken)) {
            $this->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ]);
        }

        return $this->postJson(
            action([InstitutionUserImportController::class, 'validateCsv']),
            [
                'file' => UploadedFile::fake()->createWithContent(
                    'filename.csv',
                    $fileContent
                ),
            ]
        );
    }

    private function getValidCsvRow(string $roleName, string $departmentName = ''): array
    {
        return ['39511267470', 'user name', 'some@email.com', '+372 56789566', $departmentName, $roleName];
    }

    private function getValidCsvHeader(): array
    {
        return ['Isikukood', 'Nimi', 'Meiliaadress', 'Telefoninumber', 'Üksus', 'Roll'];
    }
}
