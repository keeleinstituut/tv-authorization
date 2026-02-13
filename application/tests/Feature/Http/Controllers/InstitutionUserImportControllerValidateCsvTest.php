<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserImportController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\TestCase;
use Throwable;

class InstitutionUserImportControllerValidateCsvTest extends TestCase
{
    use EntityHelpers, RefreshDatabase;

    public function test_validation_with_correct_csv_file_returned_200(): void
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($role->name, $department->name),
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk()->assertExactJson(['errors' => [], 'rowsWithExistingInstitutionUsers' => []]);
    }

    public function test_validation_of_file_with_already_existing_institution_user_returned_200(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);
        $existingInstitutionUser = $this->createInstitutionUserWithRoles($institution, $role);
        $existingUser = $existingInstitutionUser->user;

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                [
                    $existingUser->personal_identification_code,
                    $existingUser->forename.' '.$existingUser->surname,
                    $existingInstitutionUser->email,
                    $existingInstitutionUser->phone,
                    '',
                    $role->name,
                ],
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk()->assertExactJson(['errors' => [], 'rowsWithExistingInstitutionUsers' => [0]]);
    }

    /**
     * @throws Throwable
     */
    public function test_validation_of_file_with_archived_institution_user_returned_200(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);
        $archivedInstitutionUser = $this->createInstitutionUserWithRoles($institution, $role);
        $archivedInstitutionUser->archived_at = Carbon::now();
        $archivedInstitutionUser->saveOrFail();

        $archivedUser = $archivedInstitutionUser->user;
        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                [
                    $archivedUser->personal_identification_code,
                    $archivedUser->forename.' '.$archivedUser->surname,
                    $archivedInstitutionUser->email,
                    $archivedInstitutionUser->phone,
                    '',
                    $role->name,
                ],
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk()->assertExactJson(['errors' => [], 'rowsWithExistingInstitutionUsers' => []]);
    }

    /**
     * @throws Throwable
     */
    public function test_validation_of_file_with_deactivated_institution_user_returned_200(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $deactivatedInstitutionUser = $this->createInstitutionUserWithRoles($institution);
        $deactivatedInstitutionUser->deactivation_date = Carbon::now()->subDay();
        $deactivatedInstitutionUser->saveOrFail();

        $deactivatedUser = $deactivatedInstitutionUser->user;
        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                [
                    $deactivatedUser->personal_identification_code,
                    $deactivatedUser->forename.' '.$deactivatedUser->surname,
                    $deactivatedInstitutionUser->email,
                    $deactivatedInstitutionUser->phone,
                    '',
                    $role->name,
                ],
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk()->assertExactJson(['errors' => [], 'rowsWithExistingInstitutionUsers' => [0]]);
    }

    public function test_validation_of_file_with_deleted_user_returned_200(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $institutionUser = $this->createInstitutionUserWithRoles($institution);
        $user = $institutionUser->user;
        $user->delete();

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                [
                    $user->personal_identification_code,
                    $user->forename.' '.$user->surname,
                    $institutionUser->email,
                    $institutionUser->phone,
                    '',
                    $role->name,
                ],
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk()->assertExactJson(['errors' => [], 'rowsWithExistingInstitutionUsers' => [0]]);
    }

    public function test_validation_of_file_with_deleted_institution_user_returned_200(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $institutionUser = $this->createInstitutionUserWithRoles($institution);
        $user = $institutionUser->user;
        $institutionUser->delete();
        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                [
                    $user->personal_identification_code,
                    $user->forename.' '.$user->surname,
                    $institutionUser->email,
                    $institutionUser->phone,
                    '',
                    $role->name,
                ],
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk()->assertExactJson(['errors' => [], 'rowsWithExistingInstitutionUsers' => [0]]);
    }

    public function test_validation_without_auth_token_returned_403()
    {
        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
        )->assertUnauthorized();
    }

    public function test_validation_without_corresponding_privilege_returned_403()
    {
        $institution = $this->createInstitution();

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->createInstitutionUserWithRoles(
                    $institution,
                    Role::factory()->hasAttached(
                        Privilege::firstWhere('key', PrivilegeKey::ViewUser->value)
                    )->create()
                )
            )
        )->assertForbidden();
    }

    public function test_validation_of_file_that_has_invalid_headers_returned_400()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                ['Roll', 'Isikukood', 'Nimi', 'Meiliaadress', 'Telefoninumber', 'Üksus'],
                $this->getValidCsvRow($role->name),
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertBadRequest();
    }

    public function test_validation_of_file_that_has_wrong_columns_count_returned_400()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $invalidCsvRow = $this->getValidCsvRow($role->name);
        $invalidCsvRow[] = 'some value';

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $invalidCsvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertBadRequest();
    }

    public function test_validation_of_file_that_has_wrong_email_column_value_returned_errors()
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name, $department->name);
        $csvRow[2] = 'wrongemail.com';

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
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

    public function test_validation_of_file_that_has_wrong_phone_column_value_returned_errors()
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name, $department->name);
        $csvRow[3] = '1234455678899';

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
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

    public function test_validation_of_file_that_has_wrong_name_column_value_returned_errors()
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name, $department->name);
        $csvRow[1] = 'Wrong Username23';

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
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

    public function test_validation_of_file_that_has_wrong_role_column_value_returned_errors()
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $csvRow = $this->getValidCsvRow('role-name', $department->name);

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
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

    public function test_validation_of_file_that_has_wrong_department_column_value_returned_errors()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name, 'wrong-department');
        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
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

    public function test_validation_of_file_that_has_multiple_roles_value()
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

        $roleColumnValue = implode(', ', [$role1->name, $role2->name]);
        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($roleColumnValue),
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk();
    }

    public function test_validation_of_file_that_has_empty_role_value()
    {
        $institution = $this->createInstitution();

        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow(','),
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertUnprocessable();
    }

    public function test_validation_of_file_that_has_role_from_another_institution_returned_422()
    {
        $roleFromAnotherInstitution = $this->createRoleWithPrivileges($this->createInstitution(), [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $institution = $this->createInstitution();
        $this->sendImportFileValidationRequest(
            $this->composeCsvContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($roleFromAnotherInstitution->name),
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
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

    private function composeCsvContent($rows): string
    {
        $content = '';
        foreach ($rows as $row) {
            $content .= implode(';', $row).PHP_EOL;
        }

        return $content;
    }

    private function sendImportFileValidationRequest(string $fileContent, string $accessToken = ''): TestResponse
    {
        if (filled($accessToken)) {
            $this->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ]);
        }

        $something = $this->postJson(
            action([InstitutionUserImportController::class, 'validateCsv']),
            [
                'file' => UploadedFile::fake()->createWithContent(
                    'filename.csv',
                    $fileContent
                ),
            ]
        );

        return $something;
    }

    private function getValidCsvRow(string $roleName, string $departmentName = ''): array
    {
        return ['39511267470', 'user name', 'some@email.com', '+372 56789566', $departmentName, $roleName];
    }

    private function getValidCsvHeader(): array
    {
        return ['Isikukood', 'Nimi', 'Meiliaadress', 'Telefoninumber', 'Üksus', 'Roll'];
    }

    private function getActingInstitutionUserWithAddUserPrivilege(Institution $institution): InstitutionUser
    {
        return $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges(
                $institution,
                [PrivilegeKey::AddUser]
            )
        );
    }
}
