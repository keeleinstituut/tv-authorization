<?php

namespace Feature\Routes\UsersImport;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\UsersImportController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\EntityHelpers;
use Tests\TestCase;

class FileValidationTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

    public function test_validation_with_correct_csv_file_returned_200(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($role->name),
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertExactJson(['errors' => []]);
    }

    public function test_validation_without_auth_token_returned_403()
    {
        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
        );

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_validation_without_corresponding_privilege_returned_403()
    {
        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
            $this->getAccessToken([PrivilegeKey::ActivateUser])
        );

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_validation_with_file_that_has_invalid_headers_returned_400()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                ['nimi', 'meiliaadress', 'sikukood', 'telefoninumber', 'üksus', 'roll', 'teostaja'],
                $this->getValidCsvRow($role->name),
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
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

        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $invalidCsvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_validation_with_file_that_has_wrong_email_column_value_stored_errors()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name);
        $csvRow[2] = 'wrongemail.com';

        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
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
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name);
        $csvRow[3] = '1234455678899';

        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJson([
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
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name);
        $csvRow[1] = 'Wrong Username23';

        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJson([
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
        $csvRow = $this->getValidCsvRow('role-name');
        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser])
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJson([
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

        $roleColumnValue = implode(', ', [$role1->name, $role2->name]);
        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($roleColumnValue),
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_validation_with_file_that_has_role_from_another_institution_returned_422()
    {
        $roleFromAnotherInstitution = $this->createRoleWithPrivileges($this->createInstitution(), [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $institution = $this->createInstitution();
        $response = $this->sendImportFileValidationRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($roleFromAnotherInstitution->name),
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution->id)
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJson([
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
            action([UsersImportController::class, 'validateFile']),
            [
                'file' => UploadedFile::fake()->createWithContent(
                    'filename.csv',
                    $fileContent
                ),
            ]
        );
    }

    private function getAccessToken(array $privileges, ?string $institutionId = null): string
    {
        $institutionId = $institutionId ?: Str::orderedUuid();

        return $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $institutionId,
            ],
            'privileges' => array_map(fn (PrivilegeKey $key) => $key->value, $privileges),
        ]);
    }

    private function getValidCsvRow(string $roleName): array
    {
        return ['39511267470', 'user name', 'some@email.com', '+37256789566', '', $roleName, false];
    }

    private function getValidCsvHeader(): array
    {
        return ['sikukood', 'nimi', 'meiliaadress', 'telefoninumber', 'üksus', 'roll', 'teostaja'];
    }
}
