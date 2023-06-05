<?php

namespace Tests\Feature\Routes\InstitutionUserImport;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserImportController;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

    public function test_import_with_correct_csv_file_returned_200(): void
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
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
        $accessToken = AuthHelpers::generateAccessToken(AuthHelpers::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

        $csvRow = $this->getValidCsvRow(
            implode(', ', [$role1->name, $role2->name]),
            $department->name
        );

        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $accessToken
        )->assertStatus(Response::HTTP_OK);

        $user = User::where('personal_identification_code', $csvRow[0])->first();
        $this->assertNotEmpty($user);
        $this->assertEquals($csvRow[1], implode(' ', [$user->forename, $user->surname]));

        $this->assertCount(1, $user->institutionUsers);
        $institutionUser = $user->institutionUsers->first();
        $this->assertEquals($csvRow[2], $institutionUser->email);
        $this->assertEquals($csvRow[3], $institutionUser->phone);

        $this->assertCount(2, $institutionUser->institutionUserRoles);
        $roleIds = $institutionUser->institutionUserRoles->pluck('role_id');
        $this->assertEquals([$role1->id, $role2->id], $roleIds->toArray());
        $this->assertEquals($department->id, $institutionUser->department_id);
    }

    public function test_import_already_existing_user_to_the_same_institution_dont_change_anything(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);
        $newRole = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);
        $institutionUser = $this->createInstitutionUserWithRoles($institution, $role);
        $user = $institutionUser->user;
        $csvRow = [
            $user->personal_identification_code,
            implode(' ', ["prefix$user->surname", $user->forename]),
            "prefix$institutionUser->email",
            '+372 56789566',
            '',
            $newRole->name,
        ];

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessToken(AuthHelpers::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $accessToken
        )->assertStatus(Response::HTTP_OK);

        $importedUser = User::where('personal_identification_code', $csvRow[0])->first();
        $this->assertNotEmpty($importedUser);
        $this->assertEquals($user->id, $importedUser->id);
        $this->assertEquals($user->surname, $importedUser->surname);
        $this->assertEquals($user->forename, $importedUser->forename);

        $this->assertCount(1, $user->institutionUsers);
        $importedInstitutionUser = $user->institutionUsers->first();
        $this->assertEquals($institutionUser->email, $importedInstitutionUser->email);
        $this->assertEquals($institutionUser->phone, $importedInstitutionUser->phone);

        $this->assertCount(1, $importedInstitutionUser->institutionUserRoles);
        $this->assertEquals($role->id, $importedInstitutionUser->institutionUserRoles->first()->role_id);
    }

    public function test_import_already_existing_user_to_another_institution()
    {
        $institution = $this->createInstitution();
        $alreadyExistingInstitutionUser = $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges($institution, [
                PrivilegeKey::DeactivateUser,
                PrivilegeKey::ActivateUser,
            ])
        );

        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $alreadyExistingUser = $alreadyExistingInstitutionUser->user;
        $csvRow = [
            $alreadyExistingUser->personal_identification_code,
            implode(' ', ["new$alreadyExistingUser->surname", $alreadyExistingUser->forename]),
            "new$alreadyExistingInstitutionUser->email",
            '+372 56789566',
            '',
            $role->name,
        ];

        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessToken(AuthHelpers::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            $accessToken
        );

        $response->assertStatus(Response::HTTP_OK);

        $users = User::where('personal_identification_code', $csvRow[0])->get();
        $this->assertCount(1, $users);

        /** @var User $importedUser */
        $importedUser = $users->first();
        $this->assertEquals($alreadyExistingUser->forename, $importedUser->forename);
        $this->assertEquals($alreadyExistingUser->surname, $importedUser->surname);

        $this->assertCount(2, $importedUser->institutionUsers);
    }

    public function test_import_file_with_errors(): void
    {
        $actingInstitutionUser = InstitutionUser::factory()
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessToken(AuthHelpers::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('wrong-role'),
            ]),
            $accessToken
        )->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_import_without_auth_token_returned_403(): void
    {
        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
        )->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_import_without_corresponding_privilege_returned_403()
    {
        $actingInstitutionUser = InstitutionUser::factory()
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::ActivateUser->value)
            ))
            ->create();
        $accessToken = AuthHelpers::generateAccessToken(AuthHelpers::makeTolkevaravClaimsForInstitutionUser($actingInstitutionUser));

        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
            $accessToken
        )->assertStatus(Response::HTTP_FORBIDDEN);
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
            action([InstitutionUserImportController::class, 'importCsv']),
            [
                'file' => UploadedFile::fake()->createWithContent(
                    'filename.csv',
                    $fileContent
                ),
            ]
        );
    }

    private function composeContent($rows): string
    {
        $content = '';
        foreach ($rows as $row) {
            $content .= implode(';', $row).PHP_EOL;
        }

        return $content;
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
