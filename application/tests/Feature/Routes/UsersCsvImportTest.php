<?php

namespace Tests\Feature\Routes;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\UsersImportController;
use App\Models\Institution;
use App\Models\UserToImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\EntityHelpers;
use Tests\TestCase;

class UsersCsvImportTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

    public function test_csv_upload(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name);
        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution)
        );

        $response->assertStatus(Response::HTTP_OK);
        $importedUser = UserToImport::first();

        $this->assertInstanceOf(UserToImport::class, $importedUser);
        $this->assertEquals($csvRow[0], $importedUser->personal_identification_code);
        $this->assertEquals($csvRow[1], $importedUser->name);
        $this->assertEquals($csvRow[2], $importedUser->email);
        $this->assertEquals($csvRow[3], $importedUser->phone);
        $this->assertEquals($csvRow[4], $importedUser->department);
        $this->assertEquals($csvRow[5], $role->name);
    }

    public function test_unauthorized_access()
    {
        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
        );

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_without_privilege()
    {
        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
            $this->getAccessToken([PrivilegeKey::ActivateUser])
        );

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_invalid_file_headers()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $response = $this->sendImportFileRequest(
            $this->composeContent([
                ['nimi', 'meiliaadress', 'sikukood', 'telefoninumber', 'üksus', 'roll', 'teostaja'],
                $this->getValidCsvRow($role->name),
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution)
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_invalid_columns_count()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $invalidCsvRow = $this->getValidCsvRow($role->name);
        $invalidCsvRow[] = 'some value';

        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $invalidCsvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution)
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_email_validation()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name);
        $csvRow[2] = 'wrongemail.com';

        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution)
        );

        $response->assertStatus(Response::HTTP_OK);
        $importedUser = UserToImport::first();
        $this->assertInstanceOf(UserToImport::class, $importedUser);
        $this->assertEquals(1, $importedUser->errors_count);
    }

    public function test_phone_validation()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name);
        $csvRow[3] = '1234455678899';

        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution)
        );

        $response->assertStatus(Response::HTTP_OK);
        $importedUser = UserToImport::first();
        $this->assertInstanceOf(UserToImport::class, $importedUser);
        $this->assertEquals(1, $importedUser->errors_count);
    }

    public function test_name_validation()
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $csvRow = $this->getValidCsvRow($role->name);
        $csvRow[1] = 'Wrong Username23';

        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution)
        );

        $response->assertStatus(Response::HTTP_OK);
        $importedUser = UserToImport::first();
        $this->assertInstanceOf(UserToImport::class, $importedUser);
        $this->assertEquals(1, $importedUser->errors_count);
    }

    public function test_role_validation()
    {
        $institution = $this->createInstitution();

        $csvRow = $this->getValidCsvRow('role-name');
        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution)
        );

        $response->assertStatus(Response::HTTP_OK);
        $importedUser = UserToImport::first();
        $this->assertInstanceOf(UserToImport::class, $importedUser);
        $this->assertEquals(1, $importedUser->errors_count);
    }

    public function test_multiple_roles()
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
        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow($roleColumnValue),
            ]),
            $this->getAccessToken([PrivilegeKey::AddUser], $institution)
        );

        $response->assertStatus(Response::HTTP_OK);
        $importedUser = UserToImport::first();
        $this->assertInstanceOf(UserToImport::class, $importedUser);
        $this->assertEquals(0, $importedUser->errors_count);
        $this->assertEquals($roleColumnValue, $importedUser->role);
    }

    private function composeContent($rows): string
    {
        $content = '';
        foreach ($rows as $row) {
            $content .= implode(';', $row).PHP_EOL;
        }

        return $content;
    }

    private function sendImportFileRequest(string $fileContent, string $accessToken = ''): TestResponse
    {
        if (! empty($accessToken)) {
            $this->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ]);
        }

        return $this->postJson(
            action([UsersImportController::class, 'store']),
            [
                'file' => UploadedFile::fake()->createWithContent(
                    'filename.csv',
                    $fileContent
                ),
            ]
        );
    }

    private function getAccessToken(array $privileges, ?Institution $institution = null): string
    {
        $institution = $institution ?: $this->createInstitution();
        $institutionUser = $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges($institution, $privileges)
        );

        return $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $institution->id,
            ],
            'institutionUserId' => $institutionUser->id,
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
