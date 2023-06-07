<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use League\Csv\Reader;
use Tests\AuthHelpers;
use Tests\TestCase;

class InstitutionUserControllerExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporting_institution_with_multiple_users(): void
    {
        // GIVEN there are three institutions with users
        [$firstInstitution] = Institution::factory()
            ->count(3)
            ->has(InstitutionUser::factory()
                ->has(User::factory())
                ->has(Role::factory()
                    ->hasAttached(Privilege::firstOrFail())
                    ->count(3)
                )
                ->count(10)
            )
            ->create();

        $firstInstitutionUser = $firstInstitution->institutionUsers()->firstOrFail();
        $firstInstitutionUser->roles()->attach(Role::factory()
            ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::ExportUser->value))
            ->create()
        );

        // WHEN request sent to endpoint with the token authenticating the first institution user
        $response = $this->sendGetRequestWithTokenFor($firstInstitutionUser);

        // THEN request should be a download
        $response
            ->assertSuccessful()
            ->assertDownload('exported_users.csv');

        // And file data should contain users of first institution in expected format
        $expectedResponseData = $firstInstitution->institutionUsers
            ->map(fn (InstitutionUser $institutionUser) => [
                'Isikukood' => $institutionUser->user->personal_identification_code,
                'Nimi' => "{$institutionUser->user->forename} {$institutionUser->user->surname}",
                'Meiliaadress' => $institutionUser->email ?? '',
                'Telefoninumber' => $institutionUser->phone ?? '',
                'Üksus' => $institutionUser->department?->name ?? '',
                'Roll' => $institutionUser->roles->map->name->join(', ') ?? '',
            ]);
        $actualResponseCsvDocument = Reader::createFromString($response->streamedContent())->setHeaderOffset(0);

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponseData),
            json_encode($actualResponseCsvDocument)
        );
    }

    public function test_exporting_institution_with_single_user(): void
    {
        // GIVEN there is an institution with a single user
        $singleInstitutionUser = InstitutionUser::factory()
            ->for(Institution::factory())
            ->has(User::factory())
            ->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::ExportUser->value)
            ))
            ->create();

        // And some other unrelated institutions
        Institution::factory()
            ->count(3)
            ->has(InstitutionUser::factory()
                ->has(User::factory())
                ->has(Role::factory()
                    ->hasAttached(Privilege::firstOrFail())
                    ->count(3)
                )
                ->count(10)
            )
            ->create();

        // WHEN request sent to endpoint with the single-user institution id in access token
        $response = $this->sendGetRequestWithTokenFor($singleInstitutionUser);

        // THEN request should be a download
        $response
            ->assertSuccessful()
            ->assertDownload('exported_users.csv');

        // And file data should contain only contain the "single" institution user institution
        $expectedResponseData = [[
            'Isikukood' => $singleInstitutionUser->user->personal_identification_code,
            'Nimi' => "{$singleInstitutionUser->user->forename} {$singleInstitutionUser->user->surname}",
            'Meiliaadress' => $singleInstitutionUser->email ?? '',
            'Telefoninumber' => $singleInstitutionUser->phone ?? '',
            'Üksus' => $singleInstitutionUser->department?->name ?? '',
            'Roll' => $singleInstitutionUser->roles->map->name->join(', ') ?? '',
        ]];
        $actualResponseCsvDocument = Reader::createFromString($response->streamedContent())->setHeaderOffset(0);

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponseData),
            json_encode($actualResponseCsvDocument)
        );
    }

    public function test_soft_deleted_institution_users_are_excluded(): void
    {
        // GIVEN there is an institutions with users
        $institution = Institution::factory()
            ->has(InstitutionUser::factory()
                ->has(User::factory())
                ->has(Role::factory()
                    ->hasAttached(Privilege::firstOrFail())
                    ->count(3)
                )
                ->count($institutionUsersCountBeforeSoftDeletion = 10)
            )
            ->create();

        // And one of the institution users is soft-deleted
        ($softDeletedInstitutionUser = $institution->institutionUsers()->firstOrFail())->delete();
        $this->assertSoftDeleted($softDeletedInstitutionUser);

        /** @var InstitutionUser $currentInstitutionUser */
        $currentInstitutionUser = $institution->refresh()->institutionUsers()->firstOrFail();
        $currentInstitutionUser->roles()->attach(Role::factory()
            ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::ExportUser->value))
            ->create()
        );

        // WHEN request sent to endpoint with the token authenticating current institution user
        $response = $this->sendGetRequestWithTokenFor($currentInstitutionUser);

        // THEN request should be a download
        $response
            ->assertSuccessful()
            ->assertDownload('exported_users.csv');

        // And file data should contain not contain the soft-deleted user
        $responseCsvData = collect(Reader::createFromString($response->streamedContent())->setHeaderOffset(0));
        $this->assertCount($institutionUsersCountBeforeSoftDeletion - 1, $responseCsvData);
        $this->assertNotContains($softDeletedInstitutionUser->user->personal_identificaiton_code, $responseCsvData->map->{'Isikukood'});
    }

    public function test_soft_deleted_users_are_excluded(): void
    {
        // GIVEN there is an institutions with users
        $institution = Institution::factory()
            ->has(InstitutionUser::factory()
                ->has(User::factory())
                ->has(Role::factory()
                    ->hasAttached(Privilege::firstOrFail())
                    ->count(3)
                )
                ->count($institutionUsersCountBeforeSoftDeletion = 10)
            )
            ->create();

        // And one of the users is soft-deleted
        ($softDeletedUser = $institution->institutionUsers()->firstOrFail()->user)->delete();
        $this->assertSoftDeleted($softDeletedUser);

        /** @var InstitutionUser $currentInstitutionUser */
        $currentInstitutionUser = $institution->refresh()->institutionUsers()->whereHas('user')->firstOrFail();
        $currentInstitutionUser->roles()->attach(Role::factory()
            ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::ExportUser->value))
            ->create()
        );

        // WHEN request sent to endpoint with the token authenticating current institution user
        $response = $this->sendGetRequestWithTokenFor($currentInstitutionUser);

        // THEN request should be a download
        $response
            ->assertSuccessful()
            ->assertDownload('exported_users.csv');

        // And file data should contain not contain the soft-deleted user
        $responseCsvData = collect(Reader::createFromString($response->streamedContent())->setHeaderOffset(0));
        $this->assertCount($institutionUsersCountBeforeSoftDeletion - 1, $responseCsvData);
        $this->assertNotContains($softDeletedUser->personal_identification_code, $responseCsvData->map->{'Isikukood'});
    }

    public function test_exporting_users_without_privilege(): void
    {
        // GIVEN there is an institutions with users
        $institution = Institution::factory()
            ->has(InstitutionUser::factory()
                ->has(User::factory())
                ->has(Role::factory()
                    ->hasAttached(Privilege::firstOrFail())
                    ->count(3)
                )
                ->count(10)
            )
            ->create();

        /** @var InstitutionUser $currentInstitutionUser */
        $currentInstitutionUser = $institution->refresh()->institutionUsers()->firstOrFail();
        $currentInstitutionUser->roles()->attach(Role::factory()
            ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::ViewUser->value))
            ->create()
        );

        // WHEN request sent to endpoint without the EXPORT_USER privilege in access token
        $response = $this->sendGetRequestWithTokenFor($currentInstitutionUser);

        // THEN response should indicate action is forbidden
        $response->assertForbidden();
    }

    public function test_exporting_users_without_access_token(): void
    {
        // GIVEN there is are institutions with users
        Institution::factory()
            ->count(3)
            ->has(InstitutionUser::factory()
                ->has(User::factory())
                ->has(Role::factory()
                    ->hasAttached(Privilege::firstOrFail())
                    ->count(3)
                )
                ->count(10)
            )
            ->create();

        // WHEN request sent without access token in header
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/institution-users/export-csv');

        // THEN response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    private function sendGetRequestWithTokenFor(
        InstitutionUser $institutionUser,
        array $tolkevaravClaimsOverride = []): TestResponse
    {
        $token = AuthHelpers::generateAccessTokenForInstitutionUser($institutionUser, $tolkevaravClaimsOverride);

        return $this
            ->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/institution-users/export-csv');
    }
}
