<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Tests\Feature\Models\Database;

use App\Enums\InstitutionUserStatus;
use App\Exceptions\OnlyUserUnderRootRoleException;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use function PHPUnit\Framework\assertEquals;

class InstitutionUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $createdModel = InstitutionUser::factory()->create();
        $this->assertModelExists($createdModel);
    }

    public function test_institution_exists(): void
    {
        $expectedInstitutionName = 'Eesti Keele Instituut';

        $createdRole = InstitutionUser::factory()->forInstitution(['name' => $expectedInstitutionName])->create();
        $this->assertModelExists($createdRole->institution);

        $retrievedInstitution = Institution::findOrFail($createdRole->institution->id);
        $this->assertEquals($expectedInstitutionName, $retrievedInstitution->name);
    }

    public function test_user_exists(): void
    {
        $expectedPic = '47607239590';

        $createdInstitutionUser = InstitutionUser::factory()->forUser(['personal_identification_code' => $expectedPic])->create();
        $this->assertModelExists($createdInstitutionUser->user);

        $retrievedUser = User::findOrFail($createdInstitutionUser->user->id);
        $this->assertEquals($expectedPic, $retrievedUser->personal_identification_code);
    }

    public function test_duplicate_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $referenceInstitution = Institution::factory()->create();
        $referenceUser = User::factory()->create();

        InstitutionUser::factory()
            ->count(2)
            ->for($referenceUser)
            ->for($referenceInstitution)
            ->create();
    }

    public function test_archived_records_are_excluded_by_default(): void
    {
        // GIVEN institution user in database
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create();

        // WHEN that database row has `archived_at` filled
        $institutionUser->archived_at = Date::now();
        $institutionUser->save();

        // THEN Eloquent methods should exclude that row by default
        $this->assertNull(InstitutionUser::find($institutionUser->id));
    }

    public function test_deactivated_records_are_excluded_by_default(): void
    {
        // GIVEN institution user in database
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create();

        // WHEN that database row has `deactivation_date` set to a date in the past
        $institutionUser->deactivation_date = Date::now()->subDays(3);
        $institutionUser->save();

        // THEN Eloquent methods should exclude that row by default
        $this->assertNull(InstitutionUser::find($institutionUser->id));
    }

    public function test_records_with_deactivation_date_in_future_are_not_excluded_by_default(): void
    {
        // GIVEN institution user in database
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create();

        // WHEN that database row has `deactivation_date` set to a date in the future
        $institutionUser->deactivation_date = Date::now()->addDays(3);
        $institutionUser->save();

        // THEN Eloquent methods should not exclude that row by default
        $this->assertNotNull(InstitutionUser::find($institutionUser->id));
        $this->assertModelExists(InstitutionUser::find($institutionUser->id));
    }

    public function test_deactivation_date_attribute_is_converted_correctly(): void
    {
        // GIVEN institution user in database
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create();

        // WHEN that we use model attribute to set `deactivation_date` to a Carbon instance of 2000-01-01T01:00:00(+/- EST timezone offset)
        $deactivationTime = Date::create(2000, 1, 1, 1, 0, 0, 'Europe/Tallinn');
        $institutionUser->deactivation_date = $deactivationTime;
        $institutionUser->save();

        $refreshedInstitutionUser = InstitutionUser::withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
            ->find($institutionUser->id);

        // THEN the attribute getter should return a string
        $this->assertIsString($refreshedInstitutionUser->deactivation_date);

        // And the string should equal the date in Estonia (2000-01-01), not UTC (1999-12-31)
        $this->assertEquals(
            '2000-01-01',
            $refreshedInstitutionUser->deactivation_date
        );
    }

    public function test_deactivation_date_attribute_is_set_as_date_in_estonia(): void
    {
        // GIVEN institution user in database
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create();

        // WHEN that we use model attribute to set `deactivation_date` to a Carbon instance of 1999-12-31T23:00:00Z
        $deactivationTime = Date::create(1999, 12, 31, 23, 0, 0, 'UTC');
        $institutionUser->deactivation_date = $deactivationTime;
        $institutionUser->save();

        // THEN the date written in database should be 2000-01-01 (Estonian timezone offset is +2/3h, causing date to change)
        $this->assertEquals(
            '2000-01-01',
            DB::table('institution_users')->where('id', $institutionUser->id)->value('deactivation_date')
        );

        // And the same date should be returned by the model attribute getter
        $this->assertEquals(
            '2000-01-01',
            InstitutionUser::withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
                ->find($institutionUser->id)
                ->deactivation_date
        );
    }

    public function test_record_is_considered_deactivated_past_midnight_deactivation_date_estonian_time(): void
    {
        // GIVEN institution user with a deactivation date of 2000-01-01 in the database
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create(['deactivation_date' => '2000-01-01']);

        // WHEN the current time is 1999-12-31T23:00:00Z (Estonian time: 2000-01-01T02:00:00+02:00)
        Date::setTestNow(Date::create(1999, 12, 31, 23, 0, 0, 'UTC'));

        // THEN that model methods should indicate the model is deactivated
        $this->assertTrue($institutionUser->isDeactivated());
        $this->assertEquals(InstitutionUserStatus::Deactivated, $institutionUser->getStatus());

        // And should be excluded by default
        $this->assertNull(InstitutionUser::find($institutionUser->id));
    }

    public function test_institution_user_archived_status(): void
    {
        // GIVEN institution user in database with `archived_at` having a value
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create(['archived_at' => Date::now()]);

        // THEN getStatus() should return ARCHIVED status
        $this->assertEquals(InstitutionUserStatus::Archived, $institutionUser->getStatus());

        // Even when the institution user is also deactivated
        $institutionUser->deactivation_date = Date::now()->subDays(3);
        $institutionUser->save();
        $this->assertEquals(InstitutionUserStatus::Archived, $institutionUser->refresh()->getStatus());
    }

    public function test_institution_user_status_is_deactivated_when_deactivation_date_is_past(): void
    {
        // GIVEN institution user in database with `deactivation_date` being in the past
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create(['deactivation_date' => Date::now()->subDays(3)]);

        // THEN getStatus() should return DEACTIVATED status
        $this->assertEquals(InstitutionUserStatus::Deactivated, $institutionUser->getStatus());

        // And isDeactivated() should return true
        $this->assertTrue($institutionUser->isDeactivated());
    }

    public function test_institution_user_status_is_deactivated_when_deactivation_date_is_future(): void
    {
        // GIVEN institution user in database with `deactivation_date` being in the future (and archived_at=null)
        $institutionUser = InstitutionUser::factory()
            ->for(User::factory()->create())
            ->for(Institution::factory()->create())
            ->create(['deactivation_date' => Date::now()->addDays(3), 'archived_at' => null]);

        // THEN getStatus() should return ACTIVE status
        $this->assertEquals(InstitutionUserStatus::Active, $institutionUser->getStatus());

        // And isDeactivated() should return false
        $this->assertFalse($institutionUser->isDeactivated());
    }

    public function test_institution_user_archived_at_attribute_is_converted_correctly(): void
    {
        // GIVEN institution user in database with an `archived_at` value
        $institutionUser = InstitutionUser::factory()->create(['archived_at' => Date::now()]);

        // THEN the `archived_at` property should return an instance of `CarbonInterface`
        $this->assertInstanceOf(CarbonInterface::class, $institutionUser->archived_at);
    }

    public function test_adding_role_pivots(): void
    {
        $institution = Institution::factory()->create();
        $firstRole = Role::factory()->for($institution)->create();
        $secondRole = Role::factory()->for($institution)->create();
        $institutionUser = InstitutionUser::factory()->for($institution)->create();

        $institutionUser->roles()->sync([$firstRole->id, $secondRole->id]);

        $actualRoleIds = $institutionUser->refresh()->roles->pluck('id')->toArray();
        $this->assertCount(2, $actualRoleIds);
        $this->assertContains($firstRole->id, $actualRoleIds);
        $this->assertContains($secondRole->id, $actualRoleIds);
    }

    public function test_detaching_role_pivots(): void
    {
        $institution = Institution::factory()->create();
        $firstRole = Role::factory()->for($institution)->create();
        $secondRole = Role::factory()->for($institution)->create();
        $institutionUser = InstitutionUser::factory()
            ->for($institution)
            ->hasAttached($firstRole)
            ->hasAttached($secondRole)
            ->create();

        $firstRolePivot = InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $firstRole->id,
        ]);
        $secondRolePivot = InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $secondRole->id,
        ]);

        $this->assertModelExists($firstRolePivot);
        $this->assertModelExists($secondRolePivot);

        $institutionUser->roles()->sync([]);

        $this->assertEmpty($institutionUser->refresh()->roles);
        $this->assertEmpty(InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $firstRole->id,
        ]));
        $this->assertEmpty(InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $secondRole->id,
        ]));
    }

    public function test_soft_deleting_role_pivots(): void
    {
        $institution = Institution::factory()->create();
        $firstRole = Role::factory()->for($institution)->create();
        $secondRole = Role::factory()->for($institution)->create();
        $institutionUser = InstitutionUser::factory()
            ->for($institution)
            ->hasAttached($firstRole)
            ->hasAttached($secondRole)
            ->create();

        $firstRolePivot = InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $firstRole->id,
        ]);
        $secondRolePivot = InstitutionUserRole::firstWhere([
            'institution_user_id' => $institutionUser->id,
            'role_id' => $secondRole->id,
        ]);

        $firstRolePivot->deleteOrFail();
        $secondRolePivot->deleteOrFail();

        $this->assertEmpty($institutionUser->refresh()->roles);
        $this->assertSoftDeleted($firstRolePivot);
        $this->assertSoftDeleted($secondRolePivot);
    }

    public function test_scope_status(): void
    {
        $referenceInstitution = Institution::factory()->create();

        $institutionUsersExpectedActive = [
            InstitutionUser::factory()->for($referenceInstitution)->create(),
            InstitutionUser::factory()
                ->for($referenceInstitution)
                ->create(['deactivation_date' => Date::now()->addMonth()->format('Y-m-d')]),
        ];

        $institutionUsersExpectedDeactivated = [
            InstitutionUser::factory()
                ->for($referenceInstitution)
                ->create(['deactivation_date' => Date::now()->subMonth()->format('Y-m-d')]),
            InstitutionUser::factory()
                ->for($referenceInstitution)
                ->create(['deactivation_date' => Date::now()]),
        ];

        $institutionUsersExpectedArchived = [
            InstitutionUser::factory()
                ->for($referenceInstitution)
                ->create(['archived_at' => Date::now()]),
            InstitutionUser::factory()
                ->for($referenceInstitution)
                ->create([
                    'archived_at' => Date::yesterday(),
                    'deactivation_date' => Date::now()->subMonth()->format('Y-m-d'),
                ]),
            InstitutionUser::factory()
                ->for($referenceInstitution)
                ->create([
                    'archived_at' => Date::yesterday(),
                    'deactivation_date' => Date::now()->addMonth()->format('Y-m-d'),
                ]),
        ];

        $this->assertEquals(
            collect($institutionUsersExpectedDeactivated)->pluck('id')->sort()->all(),
            InstitutionUser::status(InstitutionUserStatus::Deactivated)->pluck('id')->sort()->all()
        );

        $this->assertEquals(
            collect($institutionUsersExpectedActive)->pluck('id')->sort()->all(),
            InstitutionUser::status(InstitutionUserStatus::Active)->pluck('id')->sort()->all()
        );

        $this->assertEquals(
            collect($institutionUsersExpectedArchived)->pluck('id')->sort()->all(),
            InstitutionUser::status(InstitutionUserStatus::Archived)->pluck('id')->sort()->all()
        );
    }

    public function test_should_detect_as_only_user_with_root_role(): void
    {

        // GIVEN
        $testInstitutionUserRole = InstitutionUserRole::factory()->create();
        $testInstitutionUserRole->role->is_root = true;
        $testInstitutionUserRole->role->save();
        $testInstitutionUser = $testInstitutionUserRole->institutionUser;

        // WHEN
        $result = $testInstitutionUser->isOnlyUserWithRootRole();

        // THEN
        $this->assertTrue($result);
    }

    public function test_should_not_detect_as_only_user_with_root_role(): void
    {

        // GIVEN
        $testInstitutionUserRole = InstitutionUserRole::factory()->create();
        $testInstitutionUserRole->role->is_root = true;
        $testInstitutionUserRole->role->save();
        InstitutionUserRole::factory()->create([
            'role_id' => $testInstitutionUserRole->role->id
        ]);
        $testInstitutionUser = $testInstitutionUserRole->institutionUser;

        // WHEN
        $result = $testInstitutionUser->isOnlyUserWithRootRole();

        // THEN
        $this->assertNotTrue($result);
    }

    public function test_should_fail_deactivating_when_only_user_under_root_role(): void
    {
        // GIVEN
        $testInstitutionUserRole = InstitutionUserRole::factory()->create();
        $testInstitutionUserRole->role->is_root = true;
        $testInstitutionUserRole->role->save();
        $testInstitutionUser = $testInstitutionUserRole->institutionUser;
        $deactivationDate = Date::parse("2000-01-01")->toDateString();

        // THEN
        $this->expectException(OnlyUserUnderRootRoleException::class);

        // WHEN
        $testInstitutionUser->deactivation_date = $deactivationDate;
        $testInstitutionUser->save();
    }

    public function test_should_allow_deactivating_when_multiple_user_under_root_role(): void
    {
        // GIVEN
        $testInstitutionUserRole = InstitutionUserRole::factory()->create();
        $testInstitutionUserRole->role->is_root = true;
        $testInstitutionUserRole->role->save();
        InstitutionUserRole::factory()->create(['role_id' => $testInstitutionUserRole->role->id]);
        $testInstitutionUser = $testInstitutionUserRole->institutionUser;
        $deactivationDate = Date::parse("2000-01-01")->toDateString();

        // WHEN
        $testInstitutionUser->deactivation_date = $deactivationDate;
        $testInstitutionUser->save();

        // THEN
        $testInstitutionUser->refresh();
        assertEquals($deactivationDate, $testInstitutionUser->deactivation_date);
    }

    public function test_should_fail_archiving_when_only_user_under_root_role(): void
    {
        // GIVEN
        $testInstitutionUserRole = InstitutionUserRole::factory()->create();
        $testInstitutionUserRole->role->is_root = true;
        $testInstitutionUserRole->role->save();
        $testInstitutionUser = $testInstitutionUserRole->institutionUser;
        $archivedAt = Date::parse("2000-01-01");

        // THEN
        $this->expectException(OnlyUserUnderRootRoleException::class);

        // WHEN
        $testInstitutionUser->archived_at = $archivedAt;
        $testInstitutionUser->save();
    }

    public function test_should_allow_archiving_when_multiple_user_under_root_role(): void
    {
        // GIVEN
        $testInstitutionUserRole = InstitutionUserRole::factory()->create();
        $testInstitutionUserRole->role->is_root = true;
        $testInstitutionUserRole->role->save();
        InstitutionUserRole::factory()->create(['role_id' => $testInstitutionUserRole->role->id]);
        $testInstitutionUser = $testInstitutionUserRole->institutionUser;
        $archivedAt = Date::parse("2000-01-01");

        // WHEN
        $testInstitutionUser->archived_at = $archivedAt;
        $testInstitutionUser->save();

        // THEN
        $testInstitutionUser->refresh();
        assertEquals($archivedAt, $testInstitutionUser->archived_at);
    }

    public function test_should_fail_deletion_when_only_user_under_root_role(): void
    {
        // GIVEN
        $testInstitutionUserRole = InstitutionUserRole::factory()->create();
        $testInstitutionUserRole->role->is_root = true;
        $testInstitutionUserRole->role->save();
        $testInstitutionUser = $testInstitutionUserRole->institutionUser;

        // THEN
        $this->expectException(OnlyUserUnderRootRoleException::class);

        // WHEN
        $testInstitutionUser->delete();
    }

    public function test_should_allow_deletion_when_multiple_user_under_root_role(): void
    {
        // GIVEN
        $testInstitutionUserRole = InstitutionUserRole::factory()->create();
        $testInstitutionUserRole->role->is_root = true;
        $testInstitutionUserRole->role->save();
        InstitutionUserRole::factory()->create(['role_id' => $testInstitutionUserRole->role->id]);
        $testInstitutionUser = $testInstitutionUserRole->institutionUser;

        // WHEN
        $testInstitutionUser->delete();

        // THEN
        $this->assertSoftDeleted($testInstitutionUser);
    }
}
