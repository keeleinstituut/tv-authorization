<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserImportController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use App\Models\User;
use AuditLogClient\Enums\AuditLogEventFailureType;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\AuthHelpers;
use Tests\EntityHelpers;
use Tests\MockedAmqpPublisherTestCase;
use Throwable;

class InstitutionUserImportControllerImportTest extends MockedAmqpPublisherTestCase
{
    use RefreshDatabase, EntityHelpers;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(
            Carbon::create(2001, 5, 21, 12)
        );
    }

    public function test_import_with_correct_csv_file_returned_200(): void
    {
        $institution = $this->createInstitution();
        $department = $this->createDepartment($institution);
        $roles = collect([
            $this->createRoleWithPrivileges($institution, [
                PrivilegeKey::DeactivateUser,
                PrivilegeKey::ActivateUser,
            ]),
            $this->createRoleWithPrivileges($institution, [
                PrivilegeKey::DeactivateUser,
                PrivilegeKey::ActivateUser,
            ]),
        ]);

        $csvRow = $this->getValidCsvRow(
            $roles->pluck('name')->implode(', '),
            $department->name
        );

        $actingInstitutionUser = $this->getActingInstitutionUserWithAddUserPrivilege($institution);
        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser)
        )->assertOk();

        $user = User::where('personal_identification_code', $csvRow[0])->first();
        $this->assertNotEmpty($user);
        $this->assertEquals($csvRow[1], implode(' ', [$user->forename, $user->surname]));

        $this->assertCount(1, $user->institutionUsers);
        $institutionUser = $user->institutionUsers->first();
        $this->assertEquals($csvRow[2], $institutionUser->email);
        $this->assertEquals($csvRow[3], $institutionUser->phone);
        $this->assertEquals(
            $roles->pluck('id'),
            $institutionUser->institutionUserRoles->pluck('role_id')
        );
        $this->assertEquals($department->id, $institutionUser->department_id);

        [$pic, $fullName, $email, $phone] = $csvRow;
        [$forename, $surname] = Str::of($fullName)->explode(' ');

        $this->assertSuccessfulAuditLogMessageWasPublished(
            AuditLogEventType::CreateObject,
            $actingInstitutionUser,
            static::TRACE_ID,
            [
                'object_type' => AuditLogEventObjectType::InstitutionUser->value,
                'object_data' => [
                    'phone' => $phone,
                    'email' => $email,
                    'department_id' => $department->id,
                    'roles' => $roles->toArray(),
                    'user' => [
                        'forename' => $forename,
                        'surname' => $surname,
                        'personal_identification_code' => $pic,
                    ],
                ],
            ],
            Date::getTestNow()
        );
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

        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk();

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

        $this->amqpPublisher->shouldNotHaveReceived('publish');
    }

    /**
     * @throws Throwable
     */
    public function test_import_archived_user_dont_change_anything(): void
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
        $institutionUser->archived_at = Carbon::now()->subDay();
        $institutionUser->saveOrFail();
        $institutionUser->refresh();

        $user = $institutionUser->user;
        $csvRow = [
            $user->personal_identification_code,
            implode(' ', ["prefix$user->surname", $user->forename]),
            "prefix$institutionUser->email",
            '+372 56789566',
            '',
            $newRole->name,
        ];

        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk();

        $oldUserAttributes = $user->getAttributes();
        $user->refresh();
        $newUserAttributes = $user->getAttributes();
        $this->assertEquals($oldUserAttributes, $newUserAttributes);

        $oldInstitutionUserAttributes = $institutionUser->getAttributes();
        $institutionUser->refresh();
        $newInstitutionUserAttributes = $institutionUser->getAttributes();
        $this->assertEquals($oldInstitutionUserAttributes, $newInstitutionUserAttributes);
        $this->assertEquals([$role->id], $institutionUser->institutionUserRoles->pluck('role_id')->toArray());

        $this->amqpPublisher->shouldNotHaveReceived('publish');
    }

    /**
     * @throws Throwable
     */
    public function test_import_deactivated_user_dont_change_anything(): void
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
        $institutionUser = $this->createInstitutionUserWithRoles($institution);
        $institutionUser->deactivation_date = Carbon::now()->subDay();
        $institutionUser->saveOrFail();
        $institutionUser->refresh();

        $user = $institutionUser->user;
        $csvRow = [
            $user->personal_identification_code,
            implode(' ', ["prefix$user->surname", $user->forename]),
            "prefix$institutionUser->email",
            '+372 56789566',
            '',
            $newRole->name,
        ];

        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        )->assertOk();

        $oldUserAttributes = $user->getAttributes();
        $user->refresh();
        $newUserAttributes = $user->getAttributes();
        $this->assertEquals($oldUserAttributes, $newUserAttributes);

        $oldInstitutionUserAttributes = $institutionUser->getAttributes();
        $institutionUser->refresh();
        $newInstitutionUserAttributes = $institutionUser->getAttributes();
        $this->assertEquals($oldInstitutionUserAttributes, $newInstitutionUserAttributes);

        $this->assertNotContains([$newRole->id], $institutionUser->institutionUserRoles->pluck('role_id')->toArray());

        $this->amqpPublisher->shouldNotHaveReceived('publish');
    }

    public function test_import_acting_user_dont_change_anything(): void
    {
        $institution = $this->createInstitution();
        $newRole = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $role = Role::factory()->hasAttached(
            Privilege::firstWhere('key', PrivilegeKey::AddUser->value)
        )->create();

        $institutionUser = InstitutionUser::factory()
            ->for($institution)
            ->create();

        $institutionUser->roles()->sync($role);
        $user = $institutionUser->user;
        $csvRow = [
            $user->personal_identification_code,
            implode(' ', ["prefix$user->surname", $user->forename]),
            "prefix$institutionUser->email",
            '+372 56789566',
            '',
            $newRole->name,
        ];

        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser($institutionUser)
        )->assertOk();

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

        $this->amqpPublisher->shouldNotHaveReceived('publish');
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

        $response = $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $csvRow,
            ]),
            AuthHelpers::generateAccessTokenForInstitutionUser(
                $this->getActingInstitutionUserWithAddUserPrivilege($institution)
            )
        );

        $response->assertOk();

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
        $actingInstitutionUser = $this->getActingInstitutionUserWithAddUserPrivilege(
            $this->createInstitution()
        );

        $fileContent = $this->composeContent([
            $this->getValidCsvHeader(),
            $this->getValidCsvRow('wrong-role'),
        ]);

        $this->sendImportFileRequest(
            $fileContent,
            AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser)
        )->assertBadRequest();

        $this->assertAuditLogMessageWasPublished(
            AuditLogEventType::CreateObject,
            $actingInstitutionUser,
            AuditLogEventFailureType::UNPROCESSABLE_ENTITY,
            static::TRACE_ID,
            [
                'object_type' => AuditLogEventObjectType::InstitutionUser->value,
                'input' => ['file' => $fileContent],
            ],
            Date::getTestNow()
        );
    }

    public function test_import_without_auth_token_returned_403(): void
    {
        $this->sendImportFileRequest(
            $this->composeContent([
                $this->getValidCsvHeader(),
                $this->getValidCsvRow('some-name'),
            ]),
        )->assertUnauthorized();
    }

    public function test_import_without_corresponding_privilege_returned_403()
    {
        $institution = $this->createInstitution();
        $fileContent = $this->composeContent([
            $this->getValidCsvHeader(),
            $this->getValidCsvRow('some-name'),
        ]);
        $actingInstitutionUser = $this->createInstitutionUserWithRoles(
            $institution,
            $this->createRoleWithPrivileges(
                $institution,
                [PrivilegeKey::ActivateUser]
            )
        );
        $this->sendImportFileRequest(
            $fileContent,
            AuthHelpers::generateAccessTokenForInstitutionUser($actingInstitutionUser)
        )->assertForbidden();

        $this->assertAuditLogMessageWasPublished(
            AuditLogEventType::CreateObject,
            $actingInstitutionUser,
            AuditLogEventFailureType::FORBIDDEN,
            static::TRACE_ID,
            [
                'object_type' => AuditLogEventObjectType::InstitutionUser->value,
                'input' => ['file' => $fileContent],
            ],
            Date::getTestNow()
        );
    }

    private function sendImportFileRequest(string $fileContent, string $accessToken = ''): TestResponse
    {
        if (! empty($accessToken)) {
            $this->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
                'X-Request-Id' => static::TRACE_ID,
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
