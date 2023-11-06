<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Tests\Feature\Http\Controllers;

use App\Enums\InstitutionUserStatus;
use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserController;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Privilege;
use App\Models\Role;
use App\Util\DateUtil;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Assertions;
use Tests\AuditLogTestCase;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class InstitutionUserControllerDeactivateTest extends AuditLogTestCase
{
    use RefreshDatabase,
        InstitutionUserHelpers,
        ModelAssertions;

    /** @return array<array{CarbonInterface, string, ?string}> */
    public static function provideTestNowsAndValidFutureDeactivationDates(): array
    {
        $exampleTestNows = [
            CarbonImmutable::create(1955, 06, 04, 14, 56, 13, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(2023, 05, 19, 19, 34, 55, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(1999, 12, 31, 23, 00, 00, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(2000, 01, 01, 01, 00, 00, DateUtil::ESTONIAN_TIMEZONE),
        ];

        $validRequestDeactivationDateGenerators = [
            fn (CarbonImmutable $dateTime) => $dateTime->addDay()->format('Y-m-d'),
            fn (CarbonImmutable $dateTime) => $dateTime->addYear()->format('Y-m-d'),
        ];

        $validExistingDeactivationDateGenerators = [
            fn () => null,
            fn (CarbonImmutable $dateTime) => $dateTime->addDays(2)->format('Y-m-d'),
        ];

        return collect($exampleTestNows)
            ->crossJoin($validRequestDeactivationDateGenerators, $validExistingDeactivationDateGenerators)
            ->mapSpread(fn (CarbonImmutable $testNow, Closure $makeRequestDeactivationDate, Closure $makeExistingDeactivationDate) => [
                $testNow,
                $makeRequestDeactivationDate($testNow),
                $makeExistingDeactivationDate($testNow),
            ])
            ->mapWithKeys(fn (array $params) => [
                "now=$params[0]; requestDeactivationDate=$params[1]; existingDeactivationDate=$params[2]" => $params,
            ])
            ->all();
    }

    /** @dataProvider provideTestNowsAndValidFutureDeactivationDates */
    public function test_expected_institution_user_has_deactivation_date_when_deactivation_date_is_in_future(DateTimeInterface $testNow,
        string $requestDeactivationDate,
        ?string $existingDeactivationDate): void
    {
        // GIVEN the current date in Estonia is before the deactivation date
        Date::setTestNow($testNow);

        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles(
            function (InstitutionUser $institutionUser) use ($existingDeactivationDate) {
                $institutionUser->deactivation_date = $existingDeactivationDate;
            }
        );

        $institutionUsersWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$actingInstitutionUser, []],
            [$targetInstitutionUser, [
                'deactivation_date' => $requestDeactivationDate,
                'status' => InstitutionUserStatus::Active->value,
            ]],
        ];

        $this->assertModelsInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendDeactivateRequestWithExpectedPayloadAndHeaders(
                $targetInstitutionUser,
                $actingInstitutionUser,
                $requestDeactivationDate
            ),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $institutionUsersWithExpectedChanges,
            $targetInstitutionUser
        );

        $this->assertInstitutionUserDeactivationDateInDatabaseIs($requestDeactivationDate, $targetInstitutionUser->id);
        $this->assertInstitutionUserIsIncludedAndActive($targetInstitutionUser->id);
        $this->assertInstitutionUserRolePivotsExist($targetInstitutionUserRolePivots);

        $this->assertMessageRepresentsInstitutionUserDeactivationDateModification(
            $this->retrieveLatestAuditLogMessageBody(),
            $targetInstitutionUser,
            $actingInstitutionUser,
            $existingDeactivationDate,
            $requestDeactivationDate
        );
    }

    /** @return array<array{CarbonInterface, string, ?string}> */
    public static function provideTestNowsAndValidSameDayDeactivationDates(): array
    {
        $exampleTestNows = [
            CarbonImmutable::create(1955, 06, 04, 14, 56, 13, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(2023, 05, 19, 19, 34, 55, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(1999, 12, 31, 23, 00, 00, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(2000, 01, 01, 01, 00, 00, DateUtil::ESTONIAN_TIMEZONE),
        ];

        $validExistingDeactivationDateGenerators = [
            fn () => null,
            fn (CarbonImmutable $dateTime) => $dateTime->addDay()->format('Y-m-d'),
            fn (CarbonImmutable $dateTime) => $dateTime->addMonth()->format('Y-m-d'),
        ];

        return collect($exampleTestNows)
            ->crossJoin($validExistingDeactivationDateGenerators)
            ->mapSpread(fn (CarbonImmutable $testNow, Closure $makeExistingDeactivationDate) => [
                $testNow,
                $testNow->setTime(0, 0)->format('Y-m-d'),
                $makeExistingDeactivationDate($testNow),
            ])
            ->mapWithKeys(fn (array $params) => [
                "now=$params[0]; requestDeactivationDate=$params[1]; existingDeactivationDate=$params[2]" => $params,
            ])
            ->all();
    }

    /** @dataProvider provideTestNowsAndValidSameDayDeactivationDates */
    public function test_expected_institution_user_is_deactivated_and_roles_detached_when_deactivation_date_today(DateTimeInterface $testNow,
        string $validDeactivationDate,
        ?string $existingDeactivationDate): void
    {
        // GIVEN the current date in Estonia is the deactivation date
        Date::setTestNow($testNow);

        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles(
            function (InstitutionUser $institutionUser) use ($existingDeactivationDate) {
                $institutionUser->deactivation_date = $existingDeactivationDate;
            }
        );

        $institutionUsersWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$actingInstitutionUser, []],
            [$targetInstitutionUser, [
                'deactivation_date' => $validDeactivationDate,
                'status' => InstitutionUserStatus::Deactivated->value,
                'roles' => [],
            ]],
        ];

        $this->assertModelsInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendDeactivateRequestWithExpectedPayloadAndHeaders(
                $targetInstitutionUser,
                $actingInstitutionUser,
                $validDeactivationDate
            ),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $institutionUsersWithExpectedChanges,
            $targetInstitutionUser
        );

        $this->assertInstitutionUserDeactivationDateInDatabaseIs($validDeactivationDate, $targetInstitutionUser->id);
        $this->assertNull(InstitutionUser::find($targetInstitutionUser->id));
        $this->assertInstitutionUserRolePivotsAreMissing($targetInstitutionUserRolePivots);
    }

    /** @return array<array{CarbonInterface, ?string}> */
    public static function provideTestNowsAndValidExistingDeactivationDates(): array
    {
        $exampleTestNows = [
            CarbonImmutable::create(1955, 06, 04, 14, 56, 13, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(2023, 05, 19, 19, 34, 55, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(1999, 12, 31, 23, 00, 00, DateUtil::ESTONIAN_TIMEZONE),
            CarbonImmutable::create(2000, 01, 01, 01, 00, 00, DateUtil::ESTONIAN_TIMEZONE),
        ];

        $validExistingDeactivationDateGenerators = [
            fn () => null,
            fn (CarbonImmutable $dateTime) => $dateTime->addDay()->format('Y-m-d'),
            fn (CarbonImmutable $dateTime) => $dateTime->addMonth()->format('Y-m-d'),
            fn (CarbonImmutable $dateTime) => $dateTime->addYear()->format('Y-m-d'),
        ];

        return collect($exampleTestNows)
            ->crossJoin($validExistingDeactivationDateGenerators)
            ->mapSpread(fn (CarbonImmutable $testNow, Closure $makeExistingDeactivationDate) => [
                $testNow,
                $makeExistingDeactivationDate($testNow),
            ])
            ->mapWithKeys(fn (array $params) => [
                "now=$params[0]; existingDeactivationDate=$params[1]" => $params,
            ])
            ->all();
    }

    /** @dataProvider provideTestNowsAndValidExistingDeactivationDates */
    public function test_when_deactivation_date_is_null(DateTimeInterface $testNow, ?string $existingDeactivationDate): void
    {
        // GIVEN the current date in Estonia is the deactivation date
        Date::setTestNow($testNow);

        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles(
            function (InstitutionUser $institutionUser) use ($existingDeactivationDate) {
                $institutionUser->deactivation_date = $existingDeactivationDate;
            }
        );

        $institutionUsersWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$actingInstitutionUser, []],
            [$targetInstitutionUser, [
                'deactivation_date' => null,
                'status' => InstitutionUserStatus::Active->value,
            ]],
        ];

        $this->assertModelsInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendDeactivateRequestWithExpectedPayloadAndHeaders(
                $targetInstitutionUser,
                $actingInstitutionUser,
                null
            ),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $institutionUsersWithExpectedChanges,
            $targetInstitutionUser
        );

        $this->assertInstitutionUserDeactivationDateInDatabaseIs(null, $targetInstitutionUser->id);
        $this->assertInstitutionUserIsIncludedAndActive($targetInstitutionUser->id);
    }

    public static function provideTestNowsAndInvalidDeactivationDates(): array
    {
        $exampleTestNows = collect([
            Date::create(1955, 06, 04, 14, 56, 13),
            Date::create(2023, 05, 19, 19, 34, 55),
            Date::create(1999, 12, 31, 23, 00, 00),
            Date::create(2000, 01, 01, 01, 00, 00),
        ])->map(fn (CarbonInterface $dateTime) => $dateTime
            ->shiftTimezone(DateUtil::ESTONIAN_TIMEZONE)
            ->toImmutable()
        );

        $invalidParametersGeneratingFunctions = [
            fn (CarbonInterface $dateTime) => [
                'Deactivation date is one year and one day after now with "now" being '.$dateTime->toISOString(true) => [
                    Date::parse($dateTime, DateUtil::ESTONIAN_TIMEZONE),
                    Date::parse($dateTime, DateUtil::ESTONIAN_TIMEZONE)->setTime(0, 0)->addYear()->addDay()->format('Y-m-d'),
                ],
            ],
            fn (CarbonInterface $dateTime) => [
                'Deactivation date is yesterday with "now" being '.$dateTime->toISOString(true) => [
                    $dateTime,
                    $dateTime->setTime(0, 0)->subDay()->format('Y-m-d'),
                ],
            ],
        ];

        return collect($invalidParametersGeneratingFunctions)
            ->crossJoin($exampleTestNows)
            ->mapSpread(fn (callable $function, CarbonInterface $dateTime) => $function($dateTime))
            ->mapWithKeys(fn (array $dataSet) => $dataSet)
            ->all();
    }

    /** @dataProvider provideTestNowsAndInvalidDeactivationDates */
    public function test_nothing_is_changed_and_validation_failed_when_deactivation_date_invalid(DateTimeInterface $testNow, string $invalidDeactivationDate): void
    {
        // GIVEN that the current time causes the deactivation date to be considered invalid
        Date::setTestNow($testNow);

        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles();

        $this->assertNothingChangedAfterSendingDeactivateRequestWithCorrectHeaders(
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser,
            $invalidDeactivationDate,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /** @return array<array{Closure(InstitutionUser): void, int}> */
    public static function provideTargetInstitutionUserStateInvalidators(): array
    {
        return [
            'Target institution user was archived yesterday' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->archived_at = Carbon::now()->subDay();
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Target institution user was archived just now' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->archived_at = Carbon::now();
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Target institution user has deactivation_date yesterday' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now()->subDay();
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Target institution user has deactivation_date just now' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now();
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Target institution user is the sole root role holder' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->syncWithoutDetaching(
                        Role::factory()
                            ->for($institutionUser->institution)
                            ->create(['is_root' => true])
                    );
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Target institution user is soft-deleted' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->delete();
                },
                Response::HTTP_NOT_FOUND,
            ],
            'Target institution user’s user relation is soft-deleted' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->user->delete();
                },
                Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /**
     * @dataProvider provideTargetInstitutionUserStateInvalidators
     *
     * @param $modifyTargetInstitutionUser Closure(InstitutionUser): void
     * @param $expectedStatusCode int
     *
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_target_has_invalid_state(Closure $modifyTargetInstitutionUser, int $expectedStatusCode): void
    {
        Date::setTestNow(Date::now());
        $validDeactivationDate = $this->convertToDateString(Date::now()->addMonth());

        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles($modifyTargetInstitutionUser);

        $this->assertNothingChangedAfterSendingDeactivateRequestWithCorrectHeaders(
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser,
            $validDeactivationDate,
            $expectedStatusCode
        );
    }

    /** @return array<array{Closure(array): array, int}> */
    public static function providePayloadValuesInvalidators(): array
    {
        $invalidDeactivationDates = ['06-06-2000', '06/06/2000', '2000/06/06',
            '06.06.2000', '2000.06.06', '06.06', '06/06', '960249600', 960249600, 'tomorrow'];
        $invalidInstitutionUserIds = [null, '', ' ', 0, 1, '1', 'not-uuid'];

        return collect()
            ->merge(collect($invalidDeactivationDates)->map(fn ($val) => ['deactivation_date' => $val]))
            ->merge(collect($invalidInstitutionUserIds)->map(fn ($val) => ['institution_user_id' => $val]))
            ->mapWithKeys(fn ($invalidFragment) => [
                'Merging invalid fragment into payload: '.json_encode($invalidFragment) => [
                    fn (array $originalPayload) => [
                        ...$originalPayload,
                        ...$invalidFragment,
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                ],
            ])
            ->all();
    }

    /** @return array<array{Closure(array): array, int}> */
    public static function providePayloadKeysInvalidators(): array
    {
        return [
            'Missing key: institution_user_id' => [
                fn (array $originalPayload) => Arr::except($originalPayload, ['institution_user_id']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Missing key: deactivation_date' => [
                fn (array $originalPayload) => Arr::except($originalPayload, ['deactivation_date']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Empty payload' => [
                fn (array $originalPayload) => [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
        ];
    }

    /** @dataProvider providePayloadValuesInvalidators
     * @dataProvider providePayloadKeysInvalidators
     * @dataProvider \Tests\Feature\DataProviders::provideRandomInstitutionUserIdInvalidator
     *
     * @param $makePayloadInvalid Closure(array): array
     *
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_state_is_valid_but_payload_invalid(Closure $makePayloadInvalid, int $expectedStatusCode): void
    {
        // GIVEN that none of the request parameters are in conflict with the current date, but are invalid in other ways
        Carbon::setTestNow(Carbon::create(2000, 1, 1, 1, 1, 0, DateUtil::ESTONIAN_TIMEZONE));
        $validDeactivationDate = $this->convertToDateString(Date::now()->addMonth());

        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles();

        $invalidPayload = $makePayloadInvalid($this->createExpectedPayload($targetInstitutionUser, $validDeactivationDate));
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$targetInstitutionUser, []],
            [$actingInstitutionUser, []],
        ];

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendDeactivateRequestWithCustomPayloadAndExpectedHeaders($invalidPayload, $actingInstitutionUser),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            $expectedStatusCode
        );

        $this->assertInstitutionUserDeactivationDateInDatabaseIs(null, $targetInstitutionUser->id);
        $this->assertInstitutionUserIsIncludedAndActive($targetInstitutionUser->id);
        $this->assertInstitutionUserRolePivotsExist($targetInstitutionUserRolePivots);
    }

    /** @dataProvider \Tests\Feature\DataProviders::provideInvalidHeaderCreators
     * @param $createInvalidHeader Closure(): array
     *
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_authentication_impossible(Closure $createInvalidHeader): void
    {
        Date::setTestNow(Date::now());
        $validDeactivationDate = $this->convertToDateString(Date::now()->addMonth());

        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles();

        $expectedPayload = $this->createExpectedPayload($targetInstitutionUser, $validDeactivationDate);
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$actingInstitutionUser, []],
            [$targetInstitutionUser, []],
        ];

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendDeactivateRequestWithCustomPayloadAndHeaders($expectedPayload, $createInvalidHeader()),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            Response::HTTP_UNAUTHORIZED
        );

        $this->assertInstitutionUserIsIncludedAndActive($targetInstitutionUser->id);
        $this->assertInstitutionUserDeactivationDateInDatabaseIs(null, $targetInstitutionUser->id);
        $this->assertInstitutionUserRolePivotsExist($targetInstitutionUserRolePivots);
    }

    public function assertInstitutionUserDeactivationDateInDatabaseIs(?string $expectedDeactivationDate, string $institutionUserId): void
    {
        $this->assertDatabaseHas(
            InstitutionUser::class, [
                'id' => $institutionUserId,
                'deactivation_date' => $expectedDeactivationDate,
            ]
        );
    }

    private function sendDeactivateRequestWithExpectedPayloadAndHeaders(InstitutionUser $targetInstitutionUser,
        InstitutionUser $actingInstitutionUser,
        ?string $deactivationDate): TestResponse
    {
        return $this->sendDeactivateRequestWithCustomPayloadAndExpectedHeaders(
            $this->createExpectedPayload($targetInstitutionUser, $deactivationDate),
            $actingInstitutionUser
        );
    }

    private function sendDeactivateRequestWithCustomPayloadAndExpectedHeaders(array $payload,
        InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendDeactivateRequestWithCustomPayloadAndHeaders(
            $payload,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendDeactivateRequestWithCustomPayloadAndHeaders(array $payload, array $headers): TestResponse
    {
        return $this
            ->withHeaders(['X-Request-Id' => static::TRACE_ID, ...$headers])
            ->postJson(
                action([InstitutionUserController::class, 'deactivate']),
                $payload
            );
    }

    public function assertNothingChangedAfterSendingDeactivateRequestWithCorrectHeaders(InstitutionUser $targetInstitutionUser,
        InstitutionUser $actingInstitutionUser,
        InstitutionUser $untargetedInstitutionUser,
        string $deactivationDate,
        int $expectedStatusCode): void
    {
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$targetInstitutionUser, []],
            [$actingInstitutionUser, []],
        ];

        $deactivationDateBeforeAction = $targetInstitutionUser->deactivation_date;

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendDeactivateRequestWithExpectedPayloadAndHeaders($targetInstitutionUser, $actingInstitutionUser, $deactivationDate),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            $expectedStatusCode
        );

        $this->assertInstitutionUserDeactivationDateInDatabaseIs($deactivationDateBeforeAction, $targetInstitutionUser->id);
        $this->assertInstitutionUserRolePivotsExist($targetInstitutionUser->institutionUserRoles);
    }

    public function createExpectedPayload(InstitutionUser $targetInstitutionUser, ?string $deactivationDate): array
    {
        return [
            'institution_user_id' => $targetInstitutionUser->id,
            'deactivation_date' => $deactivationDate,
        ];
    }

    /** @param $modifyTargetUser null|Closure(InstitutionUser): void
     * @param $modifyActingUser null|Closure(InstitutionUser): void
     * @return array{
     *     targetInstitutionUser: InstitutionUser,
     *     actingInstitutionUser: InstitutionUser,
     *     untargetedInstitutionUser: InstitutionUser,
     *     targetInstitutionUserRolePivots: Collection<InstitutionUserRole>,
     * }
     *
     * @throws Throwable
     */
    public function createStandardSuccessCaseInstitutionUsersAndRoles(?Closure $modifyTargetUser = null,
        ?Closure $modifyActingUser = null): array
    {
        [
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser
        ] = $this->createThreeInstitutionUsersInSameInstitution(
            modifyFirstInstitutionUser: $modifyTargetUser,
            modifySecondInstitutionUser: function (InstitutionUser $institutionUser) use ($modifyActingUser) {
                $institutionUser->roles()->attach(
                    Role::factory()
                        ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::DeactivateUser->value))
                        ->create()
                );

                if ($modifyActingUser !== null) {
                    $modifyActingUser($institutionUser);
                }
            }
        );

        return [
            'targetInstitutionUser' => $targetInstitutionUser->refresh(),
            'actingInstitutionUser' => $actingInstitutionUser->refresh(),
            'untargetedInstitutionUser' => $untargetedInstitutionUser->refresh(),
            'targetInstitutionUserRolePivots' => $targetInstitutionUser->institutionUserRoles,
        ];
    }

    private function convertToDateString(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d');
    }

    private function assertMessageRepresentsInstitutionUserDeactivationDateModification(array $actualMessageBody, InstitutionUser $institutionUser, InstitutionUser $actingUser, ?string $expectedOldDeactivationDate, ?string $expectedNewDeactivationDate): void
    {
        $expectedMessageBodySubset = [
            'event_type' => AuditLogEventType::ModifyObject->value,
            'happened_at' => Date::getTestNow()->toISOString(),
            'trace_id' => static::TRACE_ID,
            'failure_type' => null,
            'context_institution_id' => $actingUser->institution_id,
            'context_department_id' => $actingUser->department_id,
            'acting_institution_user_id' => $actingUser->id,
            'acting_user_pic' => $actingUser->user->personal_identification_code,
            'acting_user_forename' => $actingUser->user->forename,
            'acting_user_surname' => $actingUser->user->surname,
        ];

        Assertions::assertArraysEqualIgnoringOrder(
            $expectedMessageBodySubset,
            collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
        );

        $eventParameters = data_get($actualMessageBody, 'event_parameters');
        $this->assertIsArray($eventParameters);

        $expectedEventParameters = [
            'object_type' => AuditLogEventObjectType::InstitutionUser->value,
            'object_identity_subset' => $institutionUser->getIdentitySubset(),
            'pre_modification_subset' => ['deactivation_date' => $expectedOldDeactivationDate],
            'post_modification_subset' => ['deactivation_date' => $expectedNewDeactivationDate],
        ];

        Assertions::assertArraysEqualIgnoringOrder($expectedEventParameters, $eventParameters);
    }
}
