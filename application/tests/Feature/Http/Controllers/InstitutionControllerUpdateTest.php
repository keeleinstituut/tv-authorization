<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use AuditLogClient\Enums\AuditLogEventFailureType;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Symfony\Component\HttpFoundation\Response;
use Tests\Assertions;
use Tests\AuthHelpers;
use Tests\Feature\DataProviders;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class InstitutionControllerUpdateTest extends InstitutionControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Date::setTestNow(Date::now());
    }

    /** @return array<array{
     *     modifyInstitution: Closure(Institution):void,
     *     payload: array,
     *     expectedStateOverride: array
     * }> */
    public static function provideInstitutionModifiersAndPayloadsAndExpectedState(): array
    {
        return [
            'Changing name' => [
                'modifyInstitution' => null,
                'payload' => ['name' => '   Some name '],
                'expectedStateOverride' => ['name' => 'Some name'],
            ],
            'Clearing previously filled email' => [
                'modifyInstitution' => function (Institution $institution): void {
                    $institution->email = 'old@email.com';
                },
                'payload' => ['email' => null],
                'expectedStateOverride' => [],
            ],
            'Setting previously empty email' => [
                'modifyInstitution' => function (Institution $institution): void {
                    $institution->email = null;
                },
                'payload' => ['email' => '  some@email.com  '],
                'expectedStateOverride' => ['email' => 'some@email.com'],
            ],
            'Changing email' => [
                'modifyInstitution' => null,
                'payload' => ['email' => '  some@email.com  '],
                'expectedStateOverride' => ['email' => 'some@email.com'],
            ],
            'Clearing previously filled phone' => [
                'modifyInstitution' => function (Institution $institution): void {
                    $institution->phone = '+372 51234567';
                },
                'payload' => ['email' => null],
                'expectedStateOverride' => [],
            ],
            'Setting previously empty phone' => [
                'modifyInstitution' => function (Institution $institution): void {
                    $institution->phone = null;
                },
                'payload' => ['phone' => '  +372 6123456  '],
                'expectedStateOverride' => ['phone' => '+372 6123456'],
            ],
            'Changing phone' => [
                'modifyInstitution' => null,
                'payload' => ['phone' => '  +372 7123456  '],
                'expectedStateOverride' => ['phone' => '+372 7123456'],
            ],
            'Setting previously empty short name' => [
                'modifyInstitution' => function (Institution $institution): void {
                    $institution->short_name = null;
                },
                'payload' => ['short_name' => '  NEW  '],
                'expectedStateOverride' => ['short_name' => 'NEW'],
            ],
            'Changing short name' => [
                'modifyInstitution' => function (Institution $institution): void {
                    $institution->short_name = 'OLD';
                },
                'payload' => ['short_name' => '  NEW  '],
                'expectedStateOverride' => ['short_name' => 'NEW'],
            ],
            'Several changes together' => [
                'modifyInstitution' => null,
                'payload' => static::createExampleValidPayload(),
                'expectedStateOverride' => [],
            ],
        ];
    }

    /** @return array<array{
     *     modifyInstitution: Closure(Institution):void,
     *     payload: array,
     *     expectedStateOverride: array
     * }> */
    public static function provideInstitutionWorktimeModifiersAndPayloadsAndExpectedState(): array
    {
        return [
            'Initially without working times, setting working times' => [
                'modifyInstitution' => null,
                'payload' => [
                    'worktime_timezone' => 'Europe/Tallinn',
                    'monday_worktime_start' => '08:00:00',
                    'monday_worktime_end' => '16:00:00',
                    'tuesday_worktime_start' => '08:00:00',
                    'tuesday_worktime_end' => '16:00:00',
                    'wednesday_worktime_start' => '08:00:00',
                    'wednesday_worktime_end' => '16:00:00',
                    'thursday_worktime_start' => '08:00:00',
                    'thursday_worktime_end' => '16:00:00',
                    'friday_worktime_start' => '08:00:00',
                    'friday_worktime_end' => '16:00:00',
                    'saturday_worktime_start' => null,
                    'saturday_worktime_end' => null,
                    'sunday_worktime_start' => null,
                    'sunday_worktime_end' => null,
                ],
                'expectedStateOverride' => [],
            ],
            'With existing working times, changing working times and timezone' => [
                'modifyInstitution' => function (Institution $institution) {
                    $institution->fill([
                        'worktime_timezone' => 'Europe/Tallinn',
                        'monday_worktime_start' => '08:00:00',
                        'monday_worktime_end' => '16:00:00',
                        'tuesday_worktime_start' => '08:00:00',
                        'tuesday_worktime_end' => '16:00:00',
                        'wednesday_worktime_start' => '08:00:00',
                        'wednesday_worktime_end' => '16:00:00',
                        'thursday_worktime_start' => '08:00:00',
                        'thursday_worktime_end' => '16:00:00',
                        'friday_worktime_start' => '08:00:00',
                        'friday_worktime_end' => '16:00:00',
                        'saturday_worktime_start' => null,
                        'saturday_worktime_end' => null,
                        'sunday_worktime_start' => null,
                        'sunday_worktime_end' => null,
                    ]);
                },
                'payload' => [
                    'worktime_timezone' => 'Asia/Shanghai',
                    'monday_worktime_start' => '09:00:00',
                    'monday_worktime_end' => '15:00:00',
                    'tuesday_worktime_start' => '09:00:00',
                    'tuesday_worktime_end' => '15:00:00',
                    'wednesday_worktime_start' => '09:00:00',
                    'wednesday_worktime_end' => '15:00:00',
                    'thursday_worktime_start' => '09:00:00',
                    'thursday_worktime_end' => '15:00:00',
                    'friday_worktime_start' => null,
                    'friday_worktime_end' => null,
                    'saturday_worktime_start' => null,
                    'saturday_worktime_end' => null,
                    'sunday_worktime_start' => null,
                    'sunday_worktime_end' => null,
                ],
                'expectedStateOverride' => [],
            ],
        ];
    }

    /**
     *
     * @param  null|Closure(Institution):void  $modifyInstitution
     *
     * @throws Throwable
     */
    #[DataProvider('provideInstitutionModifiersAndPayloadsAndExpectedState')]
    #[DataProvider('provideInstitutionWorktimeModifiersAndPayloadsAndExpectedState')]
    public function test_institution_is_updated_as_expected(
        ?Closure $modifyInstitution,
        array $payload,
        array $expectedStateOverride): void
    {
        Date::setTestNow(Date::now());

        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
        ] = $this->createInstitutionAndPrivilegedActingUser($modifyInstitution);

        $institutionBeforeRequest = $institution->toArray();

        $this->assertModelInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendUpdateRequestWithExpectedHeaders($institution->id, $payload, $actingInstitutionUser),
            RepresentationHelpers::createInstitutionFlatRepresentation(...),
            $institution,
            [...$payload, ...$expectedStateOverride]
        );
    }

    /** @return array<array{
     *    Closure(InstitutionUser):void,
     *    int
     * }> */
    public static function provideActingUserInvalidatorsAndExpectedResponseStatus(): array
    {
        return [
            'Acting institution user with all privileges except EDIT_INSTITUTION' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->sync(
                        Role::factory()
                            ->hasAttached(Privilege::where('key', '!=', PrivilegeKey::EditInstitution->value)->get())
                            ->create()
                    );
                },
                Response::HTTP_FORBIDDEN,
            ],
            'Acting institution user in other institution' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->institution()->associate(Institution::factory()->create());
                },
                Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /** @throws Throwable */
    #[DataProvider('provideActingUserInvalidatorsAndExpectedResponseStatus')]
    public function test_nothing_is_changed_when_acting_user_forbidden(Closure $modifyActingInstitutionUser, int $expectedResponseStatus): void
    {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
        ] = $this->createInstitutionAndPrivilegedActingUser(modifyActingInstitutionUser: $modifyActingInstitutionUser);

        $payload = static::createExampleValidPayload();
        $this->assertInstitutionUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithExpectedHeaders(
                $institution->id,
                $payload,
                $actingInstitutionUser
            ),
            $institution,
            $expectedResponseStatus
        );
    }

    /**
     * @return array<array{
     *     validInitialState: array,
     *     invalidChange: array
     * }>
     */
    public static function provideValidInitialStateAndInvalidChanges(): array
    {
        return [
            'name: ""' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'name' => ''],
            ],
            'name: "\t \n"' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'name' => "\t \n"],
            ],
            'name: null' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'name' => null],
            ],
            'email: "not email"' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'email' => 'not email'],
            ],
            'phone: "not phone"' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'phone' => 'not phone'],
            ],
            'phone: "112"' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'phone' => '112'],
            ],
            'phone: "512 8756"' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'phone' => '512 8756'],
            ],
            'phone: "+4951234567"' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'phone' => '+4951234567'],
            ],
            'short_name: "ABCD"' => [
                'validInitialState' => [],
                'invalidChange' => [...static::createExampleValidPayload(), 'short_name' => 'ABCD'],
            ],
            'short_name: ""' => [
                'validInitialState' => ['short_name' => null],
                'invalidChange' => [...static::createExampleValidPayload(), 'short_name' => ''],
            ],
        ];
    }

    /**
     * @return array<array{
     *     validInitialState: array,
     *     invalidChange: array
     * }>
     */
    public static function provideValidInitialWorktimesAndInvalidChanges(): array
    {
        return [
            'Worktime timezone missing' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'monday_worktime_start' => '08:00:00',
                    'monday_worktime_end' => '16:00:00',
                    'worktime_timezone' => null,
                ],
            ],
            'Worktime in invalid format: AM/PM' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'monday_worktime_start' => '8 AM',
                    'monday_worktime_end' => '16:00:00',
                    'worktime_timezone' => 'Europe/Tallinn',
                ],
            ],
            'Worktime in invalid format: ISO Datetime' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'monday_worktime_start' => '2023-07-01T12:00:00Z',
                    'monday_worktime_end' => '16:00:00',
                    'worktime_timezone' => 'Europe/Tallinn',
                ],
            ],
            'Not sending all worktime fields' => [
                'validInitialState' => [],
                'invalidChange' => [
                    'worktime_timezone' => 'Europe/Tallinn',
                    'monday_worktime_start' => '08:00:00',
                    'monday_worktime_end' => '16:00:00',
                ],
            ],
            'Worktimes end missing' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => 'Europe/Tallinn',
                    'monday_worktime_start' => '08:00:00',
                ],
            ],
            'Worktime start missing' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => 'Europe/Tallinn',
                    'monday_worktime_start' => null,
                    'monday_worktime_end' => '16:00:00',
                ],
            ],
            'Worktime end before start' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => 'Europe/Tallinn',
                    'monday_worktime_start' => '16:00:00',
                    'monday_worktime_end' => '08:00:00',
                ],
            ],
            'Worktime end equal to start' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => 'Europe/Tallinn',
                    'monday_worktime_start' => '08:00:00',
                    'monday_worktime_end' => '08:00:00',
                ],
            ],
            'Worktime timezone as non-IANA value: gibberish' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => 'Essos/Braavos',
                    'monday_worktime_start' => '08:00:00',
                    'monday_worktime_end' => '16:00:00',
                ],
            ],
            'Worktime timezone as non-IANA value: timezone abbreviation' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => 'PST',
                    'monday_worktime_start' => '08:00:00',
                    'monday_worktime_end' => '16:00:00',
                ],
            ],
            'Worktime timezone as non-IANA value: numerical offset (+2)' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => '+2',
                    'monday_worktime_start' => '08:00:00',
                    'monday_worktime_end' => '16:00:00',
                ],
            ],
            'Worktime timezone as non-IANA value: numerical offset (+3:00)' => [
                'validInitialState' => [],
                'invalidChange' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => '+2',
                    'monday_worktime_start' => '08:00:00',
                    'monday_worktime_end' => '16:00:00',
                ],
            ],
        ];
    }

    /**
     *
     * @throws Throwable
     */
    #[DataProvider('provideValidInitialStateAndInvalidChanges')]
    #[DataProvider('provideValidInitialWorktimesAndInvalidChanges')]
    public function test_nothing_is_changed_when_payload_invalid(array $validInitialState, array $invalidChange): void
    {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
        ] = $this->createInstitutionAndPrivilegedActingUser(
            fn (Institution $institution) => $institution->fill($validInitialState)
        );

        $this->assertInstitutionUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithExpectedHeaders($institution->id, $invalidChange, $actingInstitutionUser),
            $institution,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /** @param  Closure():array  $createHeader
     *
     * @throws Throwable
     */
    #[DataProviderExternal('Tests\Feature\DataProviders', 'provideInvalidHeaderCreators')]
    public function test_nothing_is_changed_when_authentication_impossible(Closure $createHeader): void
    {
        ['institution' => $institution] = $this->createInstitutionAndPrivilegedActingUser();

        $this->assertInstitutionUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithCustomHeaders($institution->id, self::createExampleValidPayload(), $createHeader()),
            $institution,
            Response::HTTP_UNAUTHORIZED
        );
    }

    public static function createExampleValidPayload(): array
    {
        return [
            'name' => 'Asutus',
            'phone' => '+372 51234567',
            'email' => 'info@asutus.ee',
            'short_name' => 'ASU',
        ];
    }

    private function sendUpdateRequestWithExpectedHeaders(mixed $institutionId, array $payload, InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendUpdateRequestWithCustomHeaders(
            $institutionId,
            $payload,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendUpdateRequestWithCustomHeaders(mixed $institutionId, array $payload, array $headers): TestResponse
    {
        return $this
            ->withHeaders(['X-Request-Id' => static::TRACE_ID, ...$headers])
            ->putJson(
                action(
                    [InstitutionController::class, 'update'],
                    ['institution_id' => $institutionId]
                ),
                $payload
            );
    }

    /**
     * @param  Closure(Institution):void|null  $modifyInstitution
     * @param  Closure(InstitutionUser):void|null  $modifyActingInstitutionUser
     * @return array{
     *     institution: Institution,
     *     actingInstitutionUser: InstitutionUser
     * }
     *
     * @throws Throwable
     */
    public function createInstitutionAndPrivilegedActingUser(?Closure $modifyInstitution = null,
        ?Closure $modifyActingInstitutionUser = null): array
    {
        return $this->createInstitutionAndActingUser(
            $modifyInstitution,
            function (InstitutionUser $institutionUser) use ($modifyActingInstitutionUser) {
                $institutionUser->roles()->attach(
                    Role::factory()
                        ->hasAttached(Privilege::whereIn('key', [
                            PrivilegeKey::EditInstitution->value,
                            PrivilegeKey::EditInstitutionWorktime->value,
                        ])->get())
                        ->create()
                );

                if (filled($modifyActingInstitutionUser)) {
                    $modifyActingInstitutionUser($institutionUser);
                }
            }
        );
    }

    private static function createNullWorktimeIntervals(): array
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->flatMap(fn (string $day) => [
                "{$day}_worktime_start" => null,
                "{$day}_worktime_end" => null,
            ])
            ->all();
    }

}
