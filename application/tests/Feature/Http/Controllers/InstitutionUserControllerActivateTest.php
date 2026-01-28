<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Tests\Feature\Http\Controllers;

use App\Enums\InstitutionUserStatus;
use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use App\Util\DateUtil;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use Carbon\CarbonInterval;
use Closure;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
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

class InstitutionUserControllerActivateTest extends AuditLogTestCase
{
    use InstitutionUserHelpers, ModelAssertions, RefreshDatabase;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(Carbon::create(2000, 1, 1, 1, 1, 0, DateUtil::ESTONIAN_TIMEZONE));
    }

    /** @return array<array{CarbonInterval, bool}>
     * @throws Exception
     */
    public static function provideValidDeactivationOffsetsAndNotifyUserOptions(): array
    {
        return [
            'deactivation_date=(1 year ago) notify_user=false' => [CarbonInterval::year(), false],
            'deactivation_date=(1 day ago) notify_user=false' => [CarbonInterval::day(), false],
            'deactivation_date=(1 hour ago) notify_user=false' => [CarbonInterval::hour(), false],
            'deactivation_date=(just now) notify_user=false' => [CarbonInterval::create(0), false],
            'deactivation_date=(1 year ago) notify_user=true' => [CarbonInterval::year(), true],
            'deactivation_date=(1 day ago) notify_user=true' => [CarbonInterval::day(), true],
            'deactivation_date=(1 hour ago) notify_user=true' => [CarbonInterval::hour(), true],
            'deactivation_date=(just now) notify_user=true' => [CarbonInterval::create(0), true],
        ];
    }

    #[DataProvider('provideValidDeactivationOffsetsAndNotifyUserOptions')]
    public function test_target_institution_user_is_reactivated_with_added_roles(CarbonInterval $deactivationDateBackwardOffset, bool $notifyUser): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'roles' => $roles
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles(
            function (InstitutionUser $originalTargetInstitutionUser) use ($deactivationDateBackwardOffset) {
                $originalTargetInstitutionUser->deactivation_date = Carbon::now()->sub($deactivationDateBackwardOffset);
                $originalTargetInstitutionUser->roles()->detach();
            }
        );

        $targetInstitutionUserBeforeRequest = $targetInstitutionUser->getAuditLogRepresentation();

        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$actingInstitutionUser, []],
            [$targetInstitutionUser, [
                'deactivation_date' => null,
                'status' => InstitutionUserStatus::Active->value,
                'roles' => $roles
                    ->map(fn (Role $role) => RepresentationHelpers::createRoleNestedRepresentation($role->refresh()))
                    ->all(),
            ]],
        ];

        $this->assertModelsInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendActivateRequestWithExpectedPayloadAndHeaders(
                $targetInstitutionUser,
                $actingInstitutionUser,
                $roles,
                $notifyUser
            ),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            $targetInstitutionUser
        );

        $this->assertInstitutionUserDeactivationDateInDatabaseIs(null, $targetInstitutionUser->id);
        $this->assertInstitutionUserIsIncludedAndActive($targetInstitutionUser->id);

        /** @noinspection PhpStatementHasEmptyBodyInspection */
        if ($notifyUser) {
            // TODO: Test user is notified (e.g. email is in queue)
        }
    }

    /** @return array<array{Closure(array): array, int}> */
    public static function providePayloadKeyInvalidators(): array
    {
        return [
            'Missing key: institution_user_id' => [
                fn ($originalPayload) => Arr::except($originalPayload, ['institution_user_id']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Missing key: notify_user' => [
                fn ($originalPayload) => Arr::except($originalPayload, ['notify_user']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Missing key: roles' => [
                fn ($originalPayload) => Arr::except($originalPayload, ['roles']),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
        ];
    }

    /** @return array<array{Closure(array): array, int}> */
    public static function provideNonexistentInstitutionUserIdInvalidator(): array
    {
        return [
            'Random UUID: institution_user_id' => [
                fn ($originalPayload) => [
                    ...$originalPayload,
                    'institution_user_id' => Str::orderedUuid()->toString(),
                ],
                Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /** @return array<array{Closure(array): array}> */
    public static function providePayloadValuesInvalidators(): array
    {
        $idInvalidExamples = [null, '', 0, 1, '0', '1', 'abc'];
        $notifyUserInvalidExamples = ['abc'];
        $rolesArrayInvalidExamples = [[], null, ''];

        $invalidPayloadFragments = collect()
            ->merge(collect($idInvalidExamples)->map(fn ($id) => ['institution_user_id' => $id]))
            ->merge(collect($rolesArrayInvalidExamples)->map(fn ($val) => ['roles' => $val]))
            ->merge(collect($idInvalidExamples)->map(fn ($id) => ['roles' => [$id]]))
            ->merge(collect($notifyUserInvalidExamples)->map(fn ($val) => ['notify_user' => $val]));

        return $invalidPayloadFragments
            ->mapWithKeys(fn ($invalidPayloadFragment) => [
                'Merging invalid fragment into payload: '.json_encode($invalidPayloadFragment) => [
                    fn ($originalPayload) => [
                        ...$originalPayload,
                        ...$invalidPayloadFragment,
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                ],
            ])
            ->all();
    }

    /**
     *
     * @param $makePayloadInvalid Closure(array): array
     *
     * @throws Throwable
     */
    #[DataProvider('providePayloadKeyInvalidators')]
    #[DataProvider('providePayloadValuesInvalidators')]
    #[DataProvider('provideNonexistentInstitutionUserIdInvalidator')]
    public function test_nothing_is_changed_when_state_valid_but_payload_invalid(Closure $makePayloadInvalid, int $expectedStatusCode): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'roles' => $roles,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles();

        $invalidPayload = $makePayloadInvalid($this->createExpectedPayload($targetInstitutionUser, true, $roles));
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$actingInstitutionUser, []],
            [$targetInstitutionUser, []],
        ];

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendActivateRequestWithCustomPayloadAndExpectedHeaders($invalidPayload, $actingInstitutionUser),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            $expectedStatusCode
        );
    }

    /** @return array<array{Closure(InstitutionUser): void, int}> */
    public static function provideTargetInstitutionUserInvalidators(): array
    {
        return [
            'Institution user already active' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = null;
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Institution user archived (without deactivation_date)' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->archived_at = Carbon::now()->subMonth();
                    $institutionUser->deactivation_date = null;
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Institution user archived (with deactivation_date)' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->archived_at = Carbon::now()->subMonth();
                    $institutionUser->deactivation_date = Carbon::now()->subMonth();
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Institution user’s deactivation_date is in future (tomorrow)' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now()->addDay();
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Institution user’s deactivation_date is in future (year)' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Carbon::now()->addYear();
                },
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Institution user is soft-deleted' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->delete();
                },
                Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /** @param $makeTargetInstitutionUserStateInvalid Closure(InstitutionUser): void
     *
     * @throws Throwable
     */
    #[DataProvider('provideTargetInstitutionUserInvalidators')]
    public function test_nothing_is_changed_when_target_has_invalid_state(Closure $makeTargetInstitutionUserStateInvalid,
        int $expectedStatusCode): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'roles' => $roles,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles($makeTargetInstitutionUserStateInvalid);

        $this->assertNothingChangedAfterSendingActivateRequestWithCorrectHeaders(
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser,
            $roles,
            $expectedStatusCode
        );
    }

    /** @return array<array{Closure(InstitutionUser): void, int}> */
    public static function provideActingInstitutionUserInvalidators(): array
    {
        return [
            'Acting user is in a different institution than target user' => [
                function (InstitutionUser $institutionUser) {
                    $newInstitution = Institution::factory()->create();
                    $institutionUser->institution()->associate($newInstitution);
                },
                Response::HTTP_NOT_FOUND,
            ],
            'Institution user does not have `ACTIVATE_USER` privilege' => [
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
     *
     * @throws Throwable
     */
    #[DataProvider('provideActingInstitutionUserInvalidators')]
    public function test_nothing_is_changed_when_acting_user_has_invalid_state(Closure $modifyActingInstitutionUser, int $expectedStatusCode): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'roles' => $roles,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles(
            modifyActingUser: $modifyActingInstitutionUser
        );

        $this->assertNothingChangedAfterSendingActivateRequestWithCorrectHeaders(
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser,
            $roles,
            $expectedStatusCode
        );
    }

    /** @return array<array{Closure(Role[]): void}> */
    public static function provideRolesInvalidators(): array
    {
        return [
            'First role is in another institution' => [function (array $roles) {
                /** @var Role[] $roles */
                $newInstitution = Institution::factory()->create();
                $roles[0]->institution()->associate($newInstitution);
            }],
            'First role is soft-deleted' => [function (array $roles) {
                /** @var Role[] $roles */
                $roles[0]->delete();
            }],
        ];
    }

    /** @param $modifyRoles Closure(Role[]): void
     *
     * @throws Throwable
     */
    #[DataProvider('provideRolesInvalidators')]
    public function test_nothing_is_changed_when_roles_have_invalid_state(Closure $modifyRoles): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'roles' => $roles,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles(
            modifyRoles: $modifyRoles
        );

        $this->assertNothingChangedAfterSendingActivateRequestWithCorrectHeaders(
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser,
            $roles,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /** @param $createHeaderFromInstitutionUser Closure(InstitutionUser): array
     *
     * @throws Throwable
     */
    #[DataProviderExternal('Tests\Feature\DataProviders', 'provideInvalidHeaderCreators')]
    public function test_nothing_is_changed_when_authentication_impossible(Closure $createHeaderFromInstitutionUser): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
            'actingInstitutionUser' => $actingInstitutionUser,
            'untargetedInstitutionUser' => $untargetedInstitutionUser,
            'roles' => $roles,
        ] = $this->createStandardSuccessCaseInstitutionUsersAndRoles();

        $expectedPayload = $this->createExpectedPayload($targetInstitutionUser, true, $roles);
        $customHeaders = $createHeaderFromInstitutionUser($actingInstitutionUser);
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$targetInstitutionUser, []],
        ];

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendActivateRequestWithCustomPayloadAndHeaders($expectedPayload, $customHeaders),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            Response::HTTP_UNAUTHORIZED
        );
    }

    public function assertNothingChangedAfterSendingActivateRequestWithCorrectHeaders(InstitutionUser $targetInstitutionUser,
        InstitutionUser $actingInstitutionUser,
        InstitutionUser $untargetedInstitutionUser,
        Collection $roles,
        int $expectedStatusCode): void
    {
        $modelsWithExpectedChanges = [
            [$untargetedInstitutionUser, []],
            [$actingInstitutionUser, []],
            [$targetInstitutionUser, []],
        ];

        $initialTargetDeactivationDate = $targetInstitutionUser->deactivation_date;

        $this->assertModelsInExpectedStateAfterAction(
            fn () => $this->sendActivateRequestWithExpectedPayloadAndHeaders(
                $targetInstitutionUser,
                $actingInstitutionUser,
                $roles,
                true
            ),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $modelsWithExpectedChanges,
            $expectedStatusCode
        );

        $this->assertInstitutionUserDeactivationDateInDatabaseIs($initialTargetDeactivationDate, $targetInstitutionUser->id);
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

    /**
     * @param  Collection<Role>  $roles
     */
    private function sendActivateRequestWithExpectedPayloadAndHeaders(InstitutionUser $targetInstitutionUser,
        InstitutionUser $actingInstitutionUser,
        Collection $roles,
        bool $notifyUser): TestResponse
    {
        return $this->sendActivateRequestWithCustomPayloadAndExpectedHeaders(
            $this->createExpectedPayload($targetInstitutionUser, $notifyUser, $roles),
            $actingInstitutionUser
        );
    }

    private function sendActivateRequestWithCustomPayloadAndExpectedHeaders(array $payload,
        InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendActivateRequestWithCustomPayloadAndHeaders(
            $payload,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendActivateRequestWithCustomPayloadAndHeaders(array $payload, array $headers): TestResponse
    {
        return $this
            ->withHeaders(['X-Request-Id' => static::TRACE_ID, ...$headers])
            ->postJson(
                action([InstitutionUserController::class, 'activate']),
                $payload
            );
    }

    public function createExpectedPayload(InstitutionUser $targetInstitutionUser, bool $notifyUser, Collection $roles): array
    {
        return [
            'institution_user_id' => $targetInstitutionUser->id,
            'notify_user' => $notifyUser,
            'roles' => $roles->pluck('id'),
        ];
    }

    /** @param $modifyTargetUser null|Closure(InstitutionUser): void
     * @param $modifyActingUser null|Closure(InstitutionUser): void
     * @param $modifyRoles null|Closure(Role[]): void
     * @return array{
     *     targetInstitutionUser: InstitutionUser,
     *     actingInstitutionUser: InstitutionUser,
     *     untargetedInstitutionUser: InstitutionUser,
     *     roles: Collection<Role>
     * }
     *
     * @throws Throwable
     */
    public function createStandardSuccessCaseInstitutionUsersAndRoles(?Closure $modifyTargetUser = null,
        ?Closure $modifyActingUser = null,
        ?Closure $modifyRoles = null): array
    {
        [
            $targetInstitutionUser,
            $actingInstitutionUser,
            $untargetedInstitutionUser
        ] = $this->createThreeInstitutionUsersInSameInstitution(
            modifyFirstInstitutionUser: function (InstitutionUser $institutionUser) use ($modifyTargetUser) {
                $institutionUser->deactivation_date = Carbon::now()->subMonth(); // May be overridden by $modifyTargetUser
                $institutionUser->roles()->detach();

                if ($modifyTargetUser !== null) {
                    $modifyTargetUser($institutionUser);
                }
            },
            modifySecondInstitutionUser: function (InstitutionUser $institutionUser) use ($modifyActingUser) {
                $institutionUser->roles()->attach(
                    Role::factory()
                        ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::ActivateUser->value))
                        ->create()
                );

                if ($modifyActingUser !== null) {
                    $modifyActingUser($institutionUser);
                }
            }
        );

        /** @var Collection<Role> $roles */
        $roles = collect([PrivilegeKey::ViewRole, PrivilegeKey::AddRole, PrivilegeKey::EditRole])
            ->map(fn (PrivilegeKey $key) => Privilege::firstWhere('key', $key->value))
            ->map(
                fn (Privilege $privilege) => Role::factory()
                    ->for($targetInstitutionUser->institution)
                    ->hasAttached($privilege)
                    ->create()
            );

        if ($modifyRoles !== null) {
            $modifyRoles($roles->all());
            $roles->each(fn (Role $role) => $role->saveOrFail());
        }

        return [
            'targetInstitutionUser' => $targetInstitutionUser->refresh(),
            'actingInstitutionUser' => $actingInstitutionUser->refresh(),
            'untargetedInstitutionUser' => $untargetedInstitutionUser->refresh(),
            'roles' => $roles->map(fn (Role $role) => $role->refresh()),
        ];
    }

    private function assertMessageRepresentsInstitutionUserDeactivationDateModification(array $actualMessageBody, array $targetUserBeforeRequest, InstitutionUser $actingUser, array $expectedNewRoles): void
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
            'object_identity_subset' => [
                'id' => $targetUserBeforeRequest['id'],
                'user' => [
                    'id' => $targetUserBeforeRequest['user']['id'],
                    'forename' => $targetUserBeforeRequest['user']['forename'],
                    'surname' => $targetUserBeforeRequest['user']['surname'],
                    'personal_identification_code' => $targetUserBeforeRequest['user']['personal_identification_code'],
                ],
            ],
            'pre_modification_subset' => Arr::only($targetUserBeforeRequest, ['deactivation_date', 'roles']),
            'post_modification_subset' => [
                'deactivation_date' => null,
                'roles' => $expectedNewRoles,
            ],
        ];

        Assertions::assertArraysEqualIgnoringOrder($expectedEventParameters, $eventParameters);
    }
}
