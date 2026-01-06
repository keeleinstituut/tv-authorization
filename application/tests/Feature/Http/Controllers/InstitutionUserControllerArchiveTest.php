<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\InstitutionUserStatus;
use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Privilege;
use App\Models\Role;
use App\Util\DateUtil;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Symfony\Component\HttpFoundation\Response;
use Tests\Assertions;
use Tests\AuditLogTestCase;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class InstitutionUserControllerArchiveTest extends AuditLogTestCase
{
    use InstitutionUserHelpers, ModelAssertions, RefreshDatabase;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(Carbon::create(2000, 1, 1, 1, 1, 0, DateUtil::ESTONIAN_TIMEZONE));
    }

    /** @return array<array{Closure(InstitutionUser): void}> */
    public static function provideTargetInstitutionUserValidStateModifiers(): array
    {
        return [
            'Target institution user is active' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = null;
                    $institutionUser->archived_at = null;
                },
            ],
            'Target institution user is deactivated (1 month ago)' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now()->subMonth();
                    $institutionUser->roles()->detach();
                },
            ],
            'Target institution user is deactivated (1 day ago)' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now()->subDay();
                    $institutionUser->roles()->detach();
                },
            ],
            'Target institution user is deactivated (just now)' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now();
                    $institutionUser->roles()->detach();
                },
            ],
            'Target institution user has deactivation_date in 1 day' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now()->addDay();
                },
            ],
            'Target institution user has deactivation_date in 1 month' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now()->addMonth();
                },
            ],
            'Target institution user has deactivation_date in 1 year' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now()->addYear();
                },
            ],
        ];
    }

    /** @param $modifyTargetInstitutionUser Closure(InstitutionUser): null
     *
     * @throws Throwable
     */
    #[DataProvider('provideTargetInstitutionUserValidStateModifiers')]
    public function test_target_is_archived_when_state_is_valid(Closure $modifyTargetInstitutionUser): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles($modifyTargetInstitutionUser);

        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$actingInstitutionUser, []],
            [$targetInstitutionUser, [
                'archived_at' => Carbon::now()->toJSON(),
                'status' => InstitutionUserStatus::Archived->value,
                'roles' => [],
            ]],
        ];

        $rolesBeforeRequest = data_get($targetInstitutionUser->getAuditLogRepresentation(), 'roles');

        $this->assertModelsInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendArchiveRequestWithExpectedPayloadAndHeaders($targetInstitutionUser, $actingInstitutionUser),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            $targetInstitutionUser
        );

        $this->assertInstitutionUserArchivedAtInDatabaseIs(Carbon::now()->toJSON(), $targetInstitutionUser->id);
        $this->assertNull(InstitutionUser::find($targetInstitutionUser->id));
        $this->assertInstitutionUserRolePivotsAreMissing($targetInstitutionUserRolePivots);
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

    /** @param $modifyTargetInstitutionUser Closure(InstitutionUser): void
     * @param $expectedStatusCode int
     *
     * @throws Throwable
     */
    #[DataProvider('provideTargetInstitutionUserStateInvalidators')]
    public function test_nothing_is_changed_when_target_institution_user_state_is_invalid(Closure $modifyTargetInstitutionUser,
        int $expectedStatusCode): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles($modifyTargetInstitutionUser);

        $this->assertNothingChangedAfterSendingArchiveRequestWithCorrectHeaders(
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser,
            $expectedStatusCode
        );

        $this->assertInstitutionUserRolePivotsExist($targetInstitutionUserRolePivots);
    }

    /** @return array<array{Closure(InstitutionUser): void, int}> */
    public static function provideActingInstitutionUserStateInvalidators(): array
    {
        return [
            'Acting user is in a different institution than target user' => [
                function (InstitutionUser $institutionUser) {
                    $newInstitution = Institution::factory()->create();
                    $institutionUser->institution()->associate($newInstitution);
                },
                Response::HTTP_NOT_FOUND,
            ],
            'Institution user does not have `ARCHIVE_USER` privilege' => [
                function (InstitutionUser $institutionUser) {
                    $newRole = Role::factory()
                        ->for($institutionUser->institution)
                        ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::DeactivateUser->value))
                        ->create();
                    $institutionUser->roles()->sync($newRole);
                },
                Response::HTTP_FORBIDDEN,
            ],
        ];
    }

    /** @param $modifyActingInstitutionUser Closure(InstitutionUser): void
     * @param $expectedStatusCode int
     *
     * @throws Throwable
     */
    #[DataProvider('provideActingInstitutionUserStateInvalidators')]
    public function test_nothing_is_changed_when_acting_institution_user_state_is_invalid(Closure $modifyActingInstitutionUser,
        int $expectedStatusCode): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles(modifyActingUser: $modifyActingInstitutionUser);

        $this->assertNothingChangedAfterSendingArchiveRequestWithCorrectHeaders(
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser,
            $expectedStatusCode
        );

        $this->assertInstitutionUserArchivedAtInDatabaseIs(null, $targetInstitutionUser->id);
        $this->assertInstitutionUserIsIncludedAndActive($targetInstitutionUser->id);
        $this->assertInstitutionUserRolePivotsExist($targetInstitutionUserRolePivots);
    }

    /** @param $createInvalidHeader Closure(): array
     *
     * @throws Throwable
     */
    #[DataProviderExternal('Tests\Feature\DataProviders', 'provideInvalidHeaderCreators')]
    public function test_nothing_is_changed_when_authentication_impossible(Closure $createInvalidHeader): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles();

        $expectedPayload = $this->createExpectedPayload($targetInstitutionUser);
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$targetInstitutionUser, []],
            [$actingInstitutionUser, []],
        ];

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendArchiveRequestWithCustomPayloadAndHeaders($expectedPayload, $createInvalidHeader()),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            Response::HTTP_UNAUTHORIZED
        );

        $this->assertInstitutionUserArchivedAtInDatabaseIs(null, $targetInstitutionUser->id);
        $this->assertInstitutionUserIsIncludedAndActive($targetInstitutionUser->id);
        $this->assertInstitutionUserRolePivotsExist($targetInstitutionUserRolePivots);
    }

    /** @return array<array{Closure(array): array, int}> */
    public static function providePayloadValuesInvalidators(): array
    {
        $invalidInstitutionUserIds = [null, '', ' ', 'not-uuid', 0, 1];

        return collect($invalidInstitutionUserIds)
            ->mapWithKeys(fn ($invalidInstitutionUserId) => [
                'Merging invalid fragment into payload: '.json_encode($invalidInstitutionUserId) => [
                    fn (array $originalPayload) => [
                        ...$originalPayload,
                        'institution_user_id' => $invalidInstitutionUserId,
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                ],
            ])
            ->all();
    }

    /** @return array<array{Closure(array): array, int}> */
    public static function providePayloadKeysInvalidator(): array
    {
        return [
            'Missing key: institution_user_id (i.e. empty payload)' => [
                fn () => [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
        ];
    }

    /**
     *
     * @param $makePayloadInvalid Closure(array): array
     *
     * @throws Throwable
     */
    #[DataProvider('providePayloadValuesInvalidators')]
    #[DataProvider('providePayloadKeysInvalidator')]
    #[DataProviderExternal('Tests\Feature\DataProviders', 'provideRandomInstitutionUserIdInvalidator')]
    public function test_nothing_is_changed_when_state_is_valid_but_payload_invalid(Closure $makePayloadInvalid, int $expectedStatusCode): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'targetInstitutionUserRolePivots' => $targetInstitutionUserRolePivots,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles();

        $invalidPayload = $makePayloadInvalid($this->createExpectedPayload($targetInstitutionUser));
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$targetInstitutionUser, []],
            [$actingInstitutionUser, []],
        ];

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendArchiveRequestWithCustomPayloadAndExpectedHeaders($invalidPayload, $actingInstitutionUser),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            $expectedStatusCode
        );

        $this->assertInstitutionUserArchivedAtInDatabaseIs(null, $targetInstitutionUser->id);
        $this->assertInstitutionUserIsIncludedAndActive($targetInstitutionUser->id);
        $this->assertInstitutionUserRolePivotsExist($targetInstitutionUserRolePivots);
    }

    public function assertNothingChangedAfterSendingArchiveRequestWithCorrectHeaders(InstitutionUser $targetInstitutionUser,
        InstitutionUser $actingInstitutionUser,
        InstitutionUser $untargetedInstitutionUser,
        int $expectedStatusCode): void
    {
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$targetInstitutionUser, []],
            [$actingInstitutionUser, []],
        ];

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendArchiveRequestWithExpectedPayloadAndHeaders($targetInstitutionUser, $actingInstitutionUser),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            $expectedStatusCode
        );
    }

    public function assertInstitutionUserArchivedAtInDatabaseIs(?string $expectedArchivedAt, string $institutionUserId): void
    {
        $this->assertDatabaseHas(
            InstitutionUser::class, [
                'id' => $institutionUserId,
                'archived_at' => $expectedArchivedAt,
            ]
        );
    }

    private function sendArchiveRequestWithExpectedPayloadAndHeaders(InstitutionUser $targetInstitutionUser,
        InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendArchiveRequestWithCustomPayloadAndExpectedHeaders(
            $this->createExpectedPayload($targetInstitutionUser),
            $actingInstitutionUser
        );
    }

    private function sendArchiveRequestWithCustomPayloadAndExpectedHeaders(array $payload,
        InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendArchiveRequestWithCustomPayloadAndHeaders(
            $payload,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendArchiveRequestWithCustomPayloadAndHeaders(array $payload, array $headers): TestResponse
    {
        return $this
            ->withHeaders(['X-Request-Id' => static::TRACE_ID, ...$headers])
            ->postJson(
                action([InstitutionUserController::class, 'archive']),
                $payload
            );
    }

    public function createExpectedPayload(InstitutionUser $targetInstitutionUser): array
    {
        return ['institution_user_id' => $targetInstitutionUser->id];
    }

    /** @param $modifyTargetUser null|Closure(InstitutionUser): void
     * @param $modifyActingUser null|Closure(InstitutionUser): void
     * @return array{
     *     targetInstitutionUser: InstitutionUser,
     *     actingInstitutionUser: InstitutionUser,
     *     untargetedInstitutionUser: InstitutionUser,
     *     targetInstitutionUserRolePivots: Collection<InstitutionUserRole>
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
                        ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::ArchiveUser->value))
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

    private function assertMessageRepresentsInstitutionUserArchivedAtModification(array $actualMessageBody, InstitutionUser $targetUser, array $rolesBeforeRequest, InstitutionUser $actingUser): void
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
            'object_identity_subset' => $targetUser->getIdentitySubset(),
            'pre_modification_subset' => ['archived_at' => null, ...(empty($rolesBeforeRequest) ? [] : ['roles' => $rolesBeforeRequest])],
            'post_modification_subset' => ['archived_at' => Date::getTestNow()->toISOString(), ...(empty($rolesBeforeRequest) ? [] : ['roles' => []])],
        ];

        Assertions::assertArraysEqualIgnoringOrder($expectedEventParameters, $eventParameters);
    }
}
