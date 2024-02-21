<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use AuditLogClient\Enums\AuditLogEventFailureType;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Assertions;
use Tests\AuditLogTestCase;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class InstitutionUserControllerUpdateTest extends AuditLogTestCase
{
    use InstitutionUserHelpers, ModelAssertions, RefreshDatabase;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(Carbon::now());
    }

    /** @return array<array{
     *     modifyStateUsingTargetUser: null|Closure(InstitutionUser):void,
     *     payload: array,
     *     expectedStateOverride: array
     * }> */
    public static function provideStateModifiersAndValidSimplePayloadsAndExpectedState(): array
    {
        return [
            'Multiple modifications at once' => [
                'modifyStateUsingTargetUser' => null,
                'payload' => static::createExampleValidPayload(),
                'expectedStateOverride' => [],
            ],
            'Changing forename of an active user' => [
                'modifyStateUsingTargetUser' => null,
                'payload' => ['user' => ['forename' => ' Testforename ']],
                'expectedStateOverride' => ['user' => ['forename' => 'Testforename']],
            ],
            'Changing surname of an active user' => [
                'modifyStateUsingTargetUser' => null,
                'payload' => ['user' => ['surname' => ' Testsurname ']],
                'expectedStateOverride' => ['user' => ['surname' => 'Testsurname']],
            ],
            'Changing email of an active user' => [
                'modifyStateUsingTargetUser' => null,
                'payload' => ['email' => ' testemail@singleton.ee '],
                'expectedStateOverride' => ['email' => 'testemail@singleton.ee'],
            ],
            'Changing phone of an active user' => [
                'modifyStateUsingTargetUser' => null,
                'payload' => ['phone' => ' +372 6123456 '],
                'expectedStateOverride' => ['phone' => '+372 6123456'],
            ],
            'Changing forename of a deactivated user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                },
                'payload' => ['user' => ['forename' => ' Testforename ']],
                'expectedStateOverride' => ['user' => ['forename' => 'Testforename']],
            ],
            'Changing surname of a deactivated user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                },
                'payload' => ['user' => ['surname' => ' Testsurname ']],
                'expectedStateOverride' => ['user' => ['surname' => 'Testsurname']],
            ],
            'Changing email of a deactivated user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                },
                'payload' => ['email' => ' testemail@singleton.ee '],
                'expectedStateOverride' => ['email' => 'testemail@singleton.ee'],
            ],
            'Changing phone of a deactivated user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                },
                'payload' => ['phone' => ' +372 6123456 '],
                'expectedStateOverride' => ['phone' => '+372 6123456'],
            ],
        ];
    }

    /** @return array<array{
     *     modifyStateUsingTargetUser: null|Closure(InstitutionUser):void,
     *     payload: array,
     *     expectedStateOverride: array
     * }> */
    public static function provideStateModifiersAndValidWorktimePayloadsAndExpectedState(): array
    {
        return [
            'Setting the workings hours of an active user' => [
                'modifyStateUsingTargetUser' => null,
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
            'Unsetting the workings hours of an active user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->fill([
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
                    'worktime_timezone' => null,
                    'monday_worktime_start' => null,
                    'monday_worktime_end' => null,
                    'tuesday_worktime_start' => null,
                    'tuesday_worktime_end' => null,
                    'wednesday_worktime_start' => null,
                    'wednesday_worktime_end' => null,
                    'thursday_worktime_start' => null,
                    'thursday_worktime_end' => null,
                    'friday_worktime_start' => null,
                    'friday_worktime_end' => null,
                    'saturday_worktime_start' => null,
                    'saturday_worktime_end' => null,
                    'sunday_worktime_start' => null,
                    'sunday_worktime_end' => null,
                ],
                'expectedStateOverride' => [],
            ],
            'Setting the workings hours of a deactivated user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                },
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
            'Unsetting the workings hours of a deactivated user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                    $institutionUser->fill([
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
                    'worktime_timezone' => null,
                    'monday_worktime_start' => null,
                    'monday_worktime_end' => null,
                    'tuesday_worktime_start' => null,
                    'tuesday_worktime_end' => null,
                    'wednesday_worktime_start' => null,
                    'wednesday_worktime_end' => null,
                    'thursday_worktime_start' => null,
                    'thursday_worktime_end' => null,
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

    /** @dataProvider provideStateModifiersAndValidSimplePayloadsAndExpectedState
     * @dataProvider provideStateModifiersAndValidWorktimePayloadsAndExpectedState
     *
     * @param  null|Closure(InstitutionUser):void  $modifyStateUsingTargetUser
     *
     * @throws Throwable
     */
    public function test_institution_user_is_updated_as_expected_with_simple_payload(
        ?Closure $modifyStateUsingTargetUser,
        array $payload,
        array $expectedStateOverride
    ): void {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetInstitutionUser' => $targetInstitutionUser,
        ] = $this->createInstitutionTargetUserAndPrivilegedActingUser(
            modifyStateUsingTargetUser: $modifyStateUsingTargetUser
        );

        $institutionUserBeforeRequest = $targetInstitutionUser->getAuditLogRepresentation();

        $this->assertModelInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendRequestWithExpectedHeaders($targetInstitutionUser->id, $payload, $actingInstitutionUser),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $targetInstitutionUser,
            $expectedChanges = [...$payload, ...$expectedStateOverride]
        );

        $this->assertMessageRepresentsInstitutionUserModification(
            $this->retrieveLatestAuditLogMessageBody(),
            $institutionUserBeforeRequest,
            $actingInstitutionUser,
            function (array $actualEventParameters) use ($expectedChanges, $institutionUserBeforeRequest) {
                $this->assertArrayHasSubsetIgnoringOrder(data_get($actualEventParameters, 'pre_modification_subset'), $institutionUserBeforeRequest);
                $this->assertArrayHasSubsetIgnoringOrder(data_get($actualEventParameters, 'post_modification_subset'), $expectedChanges);
            }
        );
    }

    /**
     * @dataProvider provideStateModifiersAndValidWorktimePayloadsAndExpectedState
     *
     * @throws Throwable
     */
    public function test_institution_user_own_worktimes_are_updated_as_expected_without_having_privilege(
        ?Closure $ignored,
        array $payload,
        array $expectedStateOverride
    ): void {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
        ] = $this->createInstitutionTargetUserAndPrivilegedActingUser();

        $this->assertModelInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendRequestWithExpectedHeaders($targetInstitutionUser->id, $payload, $targetInstitutionUser),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $targetInstitutionUser,
            [...$payload, ...$expectedStateOverride]
        );
    }

    /** @return array<array{
     *     modifyStateUsingTargetUser: Closure(InstitutionUser):void,
     *     createPayloadGivenTargetUser: Closure(InstitutionUser):array,
     *     createExpectedStateGivenTargetUser: Closure(InstitutionUser):array
     * }> */
    public static function provideStateModifiersAndValidDepartmentPayloadsAndExpectedState(): array
    {
        return [
            'Attaching department to an active user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    Department::factory()->for($institutionUser->institution)->create();
                },
                'createPayloadGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    'department_id' => $institutionUser->institution->departments->first()->id,
                ],
                'createExpectedStateGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    ...RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser),
                    'department' => RepresentationHelpers::createDepartmentFlatRepresentation(
                        $institutionUser->institution->departments->first()
                    ),
                ],
            ],
            'Detaching department from an active user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->department()->associate(
                        Department::factory()->for($institutionUser->institution)->create()
                    );
                },
                'createPayloadGivenInstitution' => fn () => ['department_id' => null],
                'createExpectedStateGivenInstitution' => fn (InstitutionUser $institutionUser) => [
                    ...RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser),
                    'department' => null,
                ],
            ],
            'Attaching department to deactivated user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                    Department::factory()->for($institutionUser->institution)->create();
                },
                'createPayloadGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    'department_id' => $institutionUser->institution->departments->first()->id,
                ],
                'createExpectedStateGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    ...RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser),
                    'department' => RepresentationHelpers::createDepartmentFlatRepresentation(
                        $institutionUser->institution->departments->first()
                    ),
                ],
            ],
            'Detaching department from deactivated user' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                    $institutionUser->saveOrFail();
                    $institutionUser->department()->associate(
                        Department::factory()->for($institutionUser->institution)->create()
                    );
                },
                'createPayloadGivenInstitution' => fn () => ['department_id' => null],
                'createExpectedStateGivenInstitution' => fn (InstitutionUser $institutionUser) => [
                    ...RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser),
                    'department' => null,
                ],
            ],
        ];
    }

    /** @return array<array{
     *     modifyStateUsingTargetUser: Closure(InstitutionUser):void,
     *     createPayloadGivenTargetUser: Closure(InstitutionUser):array,
     *     createExpectedStateGivenTargetUser: Closure(InstitutionUser):array
     * }> */
    public static function provideStateModifiersAndValidRolePayloadsAndExpectedState(): array
    {
        return [
            'Attaching roles' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    Role::factory()->for($institutionUser->institution)->create();
                },
                'createPayloadGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    'roles' => [$institutionUser->institution->roles->first()->id],
                ],
                'createExpectedStateGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    ...RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser),
                    'roles' => [
                        RepresentationHelpers::createRoleNestedRepresentation(
                            $institutionUser->institution->roles->first()
                        ),
                    ],
                ],
            ],
            'Detaching roles' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->sync(
                        Role::factory()->for($institutionUser->institution)->create()
                    );
                },
                'createPayloadGivenTargetUser' => fn () => ['roles' => []],
                'createExpectedStateGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    ...RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser),
                    'roles' => [],
                ],
            ],
            'Attach one role, detach one role' => [
                'modifyStateUsingTargetUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->sync(
                        Role::factory()->for($institutionUser->institution)->create()
                    );
                    Role::factory()->for($institutionUser->institution)->create();
                },
                'createPayloadGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    'roles' => $institutionUser->institution->roles
                        ->pluck('id')
                        ->diff($institutionUser->roles->pluck('id'))
                        ->all(),
                ],
                'createExpectedStateGivenTargetUser' => fn (InstitutionUser $institutionUser) => [
                    ...RepresentationHelpers::createInstitutionUserNestedRepresentation($institutionUser),
                    'roles' => $institutionUser->institution->roles
                        ->diff($institutionUser->roles)
                        ->map(RepresentationHelpers::createRoleNestedRepresentation(...))
                        ->all(),
                ],
            ],
        ];
    }

    /** @dataProvider provideStateModifiersAndValidDepartmentPayloadsAndExpectedState
     * @dataProvider provideStateModifiersAndValidRolePayloadsAndExpectedState
     *
     * @param  Closure(InstitutionUser):void  $modifyStateUsingTargetUser
     * @param  Closure(InstitutionUser):array  $createPayloadGivenTargetUser
     * @param  Closure(InstitutionUser):array  $createExpectedStateGivenTargetUser

     *
     * @throws Throwable
     */
    public function test_institution_user_is_updated_as_expected_with_referencing_payload(
        Closure $modifyStateUsingTargetUser,
        Closure $createPayloadGivenTargetUser,
        Closure $createExpectedStateGivenTargetUser
    ): void {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetInstitutionUser' => $targetInstitutionUser,
        ] = $this->createInstitutionTargetUserAndPrivilegedActingUser(
            modifyStateUsingTargetUser: $modifyStateUsingTargetUser
        );

        $this->assertModelInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendRequestWithExpectedHeaders(
                $targetInstitutionUser->id,
                $createPayloadGivenTargetUser($targetInstitutionUser),
                $actingInstitutionUser
            ),
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            $targetInstitutionUser,
            $createExpectedStateGivenTargetUser($targetInstitutionUser)
        );
    }

    /**
     * @return array<string, array{array}>
     */
    public static function provideValidInitialStateAndInvalidChanges(): array
    {
        return [
            'email: null' => [[...static::createExampleValidPayload(), 'email' => null]],
            'email: empty string' => [[...static::createExampleValidPayload(), 'email' => '']],
            'email: not-email' => [[...static::createExampleValidPayload(), 'email' => 'not-email']],
            'phone: null' => [[...static::createExampleValidPayload(), 'phone' => null]],
            'phone: empty string' => [[...static::createExampleValidPayload(), 'phone' => '']],
            'phone: -1' => [[...static::createExampleValidPayload(), 'phone' => '-1']],
            'phone: abc' => [[...static::createExampleValidPayload(), 'phone' => 'abc']],
            'phone: 5123456' => [[...static::createExampleValidPayload(), 'phone' => '5123456']],
            'phone: 5123 456' => [[...static::createExampleValidPayload(), 'phone' => '5123 456']],
            'phone: 51234567' => [[...static::createExampleValidPayload(), 'phone' => '51234567']],
            'phone: 5123 4567' => [[...static::createExampleValidPayload(), 'phone' => '5123 4567']],
            'phone: 37251234567' => [[...static::createExampleValidPayload(), 'phone' => '37251234567']],
            'phone: 372 5123 4567' => [[...static::createExampleValidPayload(), 'phone' => '372 5123 4567']],
            'phone: 372 5123 456' => [[...static::createExampleValidPayload(), 'phone' => '372 5123 456']],
            'phone: 0037251234567' => [[...static::createExampleValidPayload(), 'phone' => '0037251234567']],
            'phone: 003725123456' => [[...static::createExampleValidPayload(), 'phone' => '003725123456']],
            'phone: 00 372 5123 4567' => [[...static::createExampleValidPayload(), 'phone' => '00 372 5123 4567']],
            'phone: 00 372 5123 456' => [[...static::createExampleValidPayload(), 'phone' => '00 372 5123 456']],
            'phone: 123' => [[...static::createExampleValidPayload(), 'phone' => '123']],
            'phone: 1234567' => [[...static::createExampleValidPayload(), 'phone' => '1234567']],
            'phone: 1234 567' => [[...static::createExampleValidPayload(), 'phone' => '1234 567']],
            'phone: 12345678' => [[...static::createExampleValidPayload(), 'phone' => '12345678']],
            'phone: 1234 5678' => [[...static::createExampleValidPayload(), 'phone' => '1234 5678']],
            'phone: +37201234567' => [[...static::createExampleValidPayload(), 'phone' => '+37201234567']],
            'phone: +372 0123 4567' => [[...static::createExampleValidPayload(), 'phone' => '+372 0123 4567']],
            'phone: +37212345678' => [[...static::createExampleValidPayload(), 'phone' => '+37212345678']],
            'phone: +372 1234 5678' => [[...static::createExampleValidPayload(), 'phone' => '+372 1234 5678']],
            'phone: +37223456789' => [[...static::createExampleValidPayload(), 'phone' => '+37223456789']],
            'phone: +372 2345 6789' => [[...static::createExampleValidPayload(), 'phone' => '+372 2345 6789']],
            'phone: +37289012345' => [[...static::createExampleValidPayload(), 'phone' => '+37289012345']],
            'phone: +372 8901 2345' => [[...static::createExampleValidPayload(), 'phone' => '+372 8901 2345']],
            'phone: +37290123456' => [[...static::createExampleValidPayload(), 'phone' => '+37290123456']],
            'phone: +372 9012 3456' => [[...static::createExampleValidPayload(), 'phone' => '+372 9012 3456']],
            'phone: +372567890' => [[...static::createExampleValidPayload(), 'phone' => '+372567890']],
            'phone: +372 5678 90' => [[...static::createExampleValidPayload(), 'phone' => '+372 5678 90']],
            'phone: +372 5678 901' => [[...static::createExampleValidPayload(), 'phone' => '+372 5678 901']],
            'phone: +372 5678 9012' => [[...static::createExampleValidPayload(), 'phone' => '+372 5678 9012']],
            'phone: +372567890123' => [[...static::createExampleValidPayload(), 'phone' => '+372567890123']],
            'phone: 372 5678 9012 3' => [[...static::createExampleValidPayload(), 'phone' => '372 5678 9012 3']],
            'phone: +372 5 6 7 8 9 0' => [[...static::createExampleValidPayload(), 'phone' => '+372 5 6 7 8 9 0']],
            'phone: +3 7 2 5 6 7 8 9 0' => [[...static::createExampleValidPayload(), 'phone' => '+3 7 2 5 6 7 8 9 0']],
            'phone: + 372 567890' => [[...static::createExampleValidPayload(), 'phone' => '+ 372 567890']],
            'phone:  +372 567890' => [[...static::createExampleValidPayload(), 'phone' => ' +372 567890']],
            'roles: null' => [[...static::createExampleValidPayload(), 'roles' => null]],
            'roles: empty string' => [[...static::createExampleValidPayload(), 'roles' => '']],
            'roles: null in array' => [[...static::createExampleValidPayload(), 'roles' => [null]]],
            'roles: empty string in array' => [[...static::createExampleValidPayload(), 'roles' => ['']]],
            'roles: abc in array' => [[...static::createExampleValidPayload(), 'roles' => ['abc']]],
            'roles: 1 in array' => [[...static::createExampleValidPayload(), 'roles' => [1]]],
            'user: null' => [[...static::createExampleValidPayload(), 'user' => null]],
            'user: empty string' => [[...static::createExampleValidPayload(), 'user' => '']],
            'user: empty array' => [[...static::createExampleValidPayload(), 'user' => []]],
            'department_id: 1' => [[...static::createExampleValidPayload(), 'department_id' => 1]],
            'department_id: abc' => [[...static::createExampleValidPayload(), 'department_id' => 'abc']],
            'user.forename: empty string' => [[...static::createExampleValidPayload(), 'user' => ['forename' => '']]],
            'user.forename: null' => [[...static::createExampleValidPayload(), 'user' => ['forename' => null]]],
            'user.surname: empty string' => [[...static::createExampleValidPayload(), 'user' => ['surname' => '']]],
            'user.surname: null' => [[...static::createExampleValidPayload(), 'user' => ['surname' => null]]],
        ];
    }

    /**
     * @return array<string, array{array}>
     */
    public static function provideValidInitialWorktimesAndInvalidChanges(): array
    {
        return [
            'Worktime timezone missing' => [[
                ...static::createNullWorktimeIntervals(),
                'monday_worktime_start' => '08:00:00',
                'monday_worktime_end' => '16:00:00',
                'worktime_timezone' => null,
            ]],
            'Worktime in invalid format: AM/PM' => [[
                ...static::createNullWorktimeIntervals(),
                'monday_worktime_start' => '8 AM',
                'monday_worktime_end' => '16:00:00',
                'worktime_timezone' => 'Europe/Tallinn',
            ]],
            'Worktime in invalid format: ISO Datetime' => [[
                ...static::createNullWorktimeIntervals(),
                'monday_worktime_start' => '2023-07-01T12:00:00Z',
                'monday_worktime_end' => '16:00:00',
                'worktime_timezone' => 'Europe/Tallinn',
            ]],
            'Not sending all worktime fields' => [[
                'worktime_timezone' => 'Europe/Tallinn',
                'monday_worktime_start' => '08:00:00',
                'monday_worktime_end' => '16:00:00',
            ]],
            'Worktimes end missing' => [[
                ...static::createNullWorktimeIntervals(),
                'worktime_timezone' => 'Europe/Tallinn',
                'monday_worktime_start' => '08:00:00',
            ]],
            'Worktime start missing' => [[
                ...static::createNullWorktimeIntervals(),
                'worktime_timezone' => 'Europe/Tallinn',
                'monday_worktime_start' => null,
                'monday_worktime_end' => '16:00:00',
            ]],
            'Worktime end before start' => [[
                ...static::createNullWorktimeIntervals(),
                'worktime_timezone' => 'Europe/Tallinn',
                'monday_worktime_start' => '16:00:00',
                'monday_worktime_end' => '08:00:00',
            ]],
            'Worktime end equal to start' => [[
                ...static::createNullWorktimeIntervals(),
                'worktime_timezone' => 'Europe/Tallinn',
                'monday_worktime_start' => '08:00:00',
                'monday_worktime_end' => '08:00:00',
            ]],
            'Worktime timezone as non-IANA value: gibberish' => [[
                ...static::createNullWorktimeIntervals(),
                'worktime_timezone' => 'Essos/Braavos',
                'monday_worktime_start' => '08:00:00',
                'monday_worktime_end' => '16:00:00',
            ]],
            'Worktime timezone as non-IANA value: timezone abbreviation' => [[
                ...static::createNullWorktimeIntervals(),
                'worktime_timezone' => 'PST',
                'monday_worktime_start' => '08:00:00',
                'monday_worktime_end' => '16:00:00',
            ]],
            'Worktime timezone as non-IANA value: numerical offset (+2)' => [[
                ...static::createNullWorktimeIntervals(),
                'worktime_timezone' => '+2',
                'monday_worktime_start' => '08:00:00',
                'monday_worktime_end' => '16:00:00',
            ]],
            'Worktime timezone as non-IANA value: numerical offset (+3:00)' => [[
                ...static::createNullWorktimeIntervals(),
                'worktime_timezone' => '+2',
                'monday_worktime_start' => '08:00:00',
                'monday_worktime_end' => '16:00:00',
            ]],
        ];
    }

    /**
     * @dataProvider provideValidInitialStateAndInvalidChanges
     * @dataProvider provideValidInitialWorktimesAndInvalidChanges
     *
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_payload_has_basic_validation_problems(array $invalidPayload): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetInstitutionUser' => $targetInstitutionUser,
        ] = $this->createInstitutionTargetUserAndPrivilegedActingUser();

        $this->assertInstitutionUserUnchangedAfterAction(
            fn () => $this->sendRequestWithExpectedHeaders(
                $targetInstitutionUser->id,
                $invalidPayload,
                $actingInstitutionUser
            ),
            $targetInstitutionUser,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $actualMessageBody = $this->retrieveLatestAuditLogMessageBody();

        $expectedMessageBodySubset = [
            'event_type' => AuditLogEventType::ModifyObject->value,
            'happened_at' => Date::getTestNow()->toISOString(),
            'trace_id' => static::TRACE_ID,
            'failure_type' => AuditLogEventFailureType::UNPROCESSABLE_ENTITY->value,
            'context_institution_id' => $actingInstitutionUser->institution_id,
            'context_department_id' => $actingInstitutionUser->department_id,
            'acting_institution_user_id' => $actingInstitutionUser->id,
            'acting_user_pic' => $actingInstitutionUser->user->personal_identification_code,
            'acting_user_forename' => $actingInstitutionUser->user->forename,
            'acting_user_surname' => $actingInstitutionUser->user->surname,
        ];

        Assertions::assertArraysEqualIgnoringOrder(
            $expectedMessageBodySubset,
            collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
        );

        $eventParameters = data_get($actualMessageBody, 'event_parameters');
        $this->assertIsArray($eventParameters);

        $expectedEventParameters = [
            'object_type' => AuditLogEventObjectType::InstitutionUser->value,
            'object_identity_subset' => $targetInstitutionUser->getIdentitySubset(),
            'input' => static::convertTrimWhiteSpaceToNullRecursively($invalidPayload),
        ];
        Assertions::assertArraysEqualIgnoringOrder(
            $expectedEventParameters,
            $eventParameters
        );
    }

    /** @return array<array{
     *     transformStateAndGenerateInvalidPayload: Closure(InstitutionUser):array,
     *     expectedStatusCode: int
     * }> */
    public static function provideStateModifiersAndInvalidPayloadCreators(): array
    {
        return [
            'Attaching role from another institution' => [
                'transformStateAndGenerateInvalidPayload' => fn () => [
                    'roles' => [Role::factory()->for(Institution::factory())->create()->id],
                ],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Attaching department from another institution' => [
                'transformStateAndGenerateInvalidPayload' => fn () => [
                    'department_id' => Department::factory()->for(Institution::factory())->create()->id,
                ],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Attaching nonexistant department' => [
                'transformStateAndGenerateInvalidPayload' => fn () => [
                    'department_id' => Str::uuid()->toString(),
                ],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Attaching nonexistant role' => [
                'transformStateAndGenerateInvalidPayload' => fn () => [
                    'roles' => [Str::uuid()->toString()],
                ],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Attaching roles when user is deactivated' => [
                'transformStateAndGenerateInvalidPayload' => function (InstitutionUser $institutionUser) {
                    $institutionUser->deactivation_date = Date::yesterday();
                    $institutionUser->saveOrFail();
                    $role = Role::factory()->for($institutionUser->institution)->create();

                    return [
                        'roles' => [$role->id],
                    ];
                },
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            'Detaching root role when the target user is its sole holder' => [
                'transformStateAndGenerateInvalidPayload' => function (InstitutionUser $institutionUser) {
                    $rootRole = $institutionUser->institution->roles()->firstWhere('is_root', true)
                        ?? Role::factory()->for($institutionUser->institution)->create(['is_root' => true]);

                    $institutionUser->roles()->sync($rootRole);
                    throw_unless($institutionUser->isOnlyUserWithRootRole());

                    return [
                        'roles' => [],
                    ];
                },
                'expectedStatusCode' => Response::HTTP_BAD_REQUEST,
            ],
        ];
    }

    /**
     * @dataProvider provideStateModifiersAndInvalidPayloadCreators
     *
     * @param  Closure(InstitutionUser):array  $transformStateAndGenerateInvalidPayload
     *
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_payload_is_invalid_considering_given_state(
        Closure $transformStateAndGenerateInvalidPayload,
        int $expectedResponseCode
    ): void {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetInstitutionUser' => $targetInstitutionUser,
        ] = $this->createInstitutionTargetUserAndPrivilegedActingUser();

        $invalidPayload = $transformStateAndGenerateInvalidPayload($targetInstitutionUser);

        $this->assertInstitutionUserUnchangedAfterAction(
            fn () => $this->sendRequestWithExpectedHeaders(
                $targetInstitutionUser->refresh()->id,
                $invalidPayload,
                $actingInstitutionUser->refresh()
            ),
            $targetInstitutionUser->refresh(),
            $expectedResponseCode
        );
    }

    /** @return array<array{
     *    modifyActingInstitutionUser: Closure(InstitutionUser):void,
     *    payload: array,
     *    expectedResponseStatus: int
     * }> */
    public static function provideActingUserInvalidatorsAndExpectedResponseStatus(): array
    {
        return [
            'Attempting to update institution user without acting user having EDIT_USER privilege' => [
                'modifyActingInstitutionUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->sync(
                        Role::factory()
                            ->hasAttached(
                                Privilege::whereNot('key', PrivilegeKey::EditUser->value)->get()
                            )
                            ->create()
                    );
                },
                'payload' => ['email' => 'some@email.com'],
                'expectedResponseStatus' => Response::HTTP_FORBIDDEN,
            ],
            'Attempting to update institution user worktime without acting user having EDIT_USER_WORKTIME privilege' => [
                'modifyActingInstitutionUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->sync(
                        Role::factory()
                            ->hasAttached(
                                Privilege::whereNot('key', PrivilegeKey::EditUserWorktime->value)->get()
                            )
                            ->create()
                    );
                },
                'payload' => [
                    ...static::createNullWorktimeIntervals(),
                    'worktime_timezone' => 'Asia/Singapore',
                    'monday_worktime_start' => '14:00:00',
                    'monday_worktime_end' => '22:00:00',
                ],
                'expectedResponseStatus' => Response::HTTP_FORBIDDEN,
            ],
            'Acting institution user in other institution' => [
                'modifyActingInstitutionUser' => function (InstitutionUser $institutionUser) {
                    $institutionUser->institution()->associate(Institution::factory()->create());
                },
                'payload' => static::createExampleValidPayload(),
                'expectedResponseStatus' => Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /** @dataProvider provideActingUserInvalidatorsAndExpectedResponseStatus
     * @param  Closure(InstitutionUser):void  $modifyActingInstitutionUser
     *
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_acting_user_forbidden(
        Closure $modifyActingInstitutionUser,
        array $payload,
        int $expectedResponseStatus
    ): void {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetInstitutionUser' => $targetInstitutionUser,
        ] = $this->createInstitutionTargetUserAndPrivilegedActingUser(modifyStateUsingActingUser: $modifyActingInstitutionUser);

        $this->assertInstitutionUserUnchangedAfterAction(
            fn () => $this->sendRequestWithExpectedHeaders(
                $targetInstitutionUser->id,
                $payload,
                $actingInstitutionUser
            ),
            $targetInstitutionUser,
            $expectedResponseStatus
        );

        if ($expectedResponseStatus === Response::HTTP_FORBIDDEN) {
            $actualMessageBody = $this->retrieveLatestAuditLogMessageBody();

            $expectedMessageBodySubset = [
                'event_type' => AuditLogEventType::ModifyObject->value,
                'happened_at' => Date::getTestNow()->toISOString(),
                'trace_id' => static::TRACE_ID,
                'failure_type' => AuditLogEventFailureType::FORBIDDEN->value,
                'context_institution_id' => $actingInstitutionUser->institution_id,
                'context_department_id' => $actingInstitutionUser->department_id,
                'acting_institution_user_id' => $actingInstitutionUser->id,
                'acting_user_pic' => $actingInstitutionUser->user->personal_identification_code,
                'acting_user_forename' => $actingInstitutionUser->user->forename,
                'acting_user_surname' => $actingInstitutionUser->user->surname,
            ];

            Assertions::assertArraysEqualIgnoringOrder(
                $expectedMessageBodySubset,
                collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
            );

            $eventParameters = data_get($actualMessageBody, 'event_parameters');
            $this->assertIsArray($eventParameters);

            $expectedEventParameters = [
                'object_type' => AuditLogEventObjectType::InstitutionUser->value,
                'object_identity_subset' => $targetInstitutionUser->getIdentitySubset(),
                'input' => $payload,
            ];
            Assertions::assertArraysEqualIgnoringOrder(
                $expectedEventParameters,
                $eventParameters
            );
        }
    }

    /** @dataProvider \Tests\Feature\DataProviders::provideInvalidHeaderCreators
     * @param  Closure():array  $createHeader
     *
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_authentication_impossible(Closure $createHeader): void
    {
        [
            'targetInstitutionUser' => $targetInstitutionUser,
        ] = $this->createInstitutionTargetUserAndPrivilegedActingUser();

        $this->assertInstitutionUserUnchangedAfterAction(
            fn () => $this->sendRequestWithCustomHeaders(
                $targetInstitutionUser->id,
                static::createExampleValidPayload(),
                $createHeader()
            ),
            $targetInstitutionUser,
            Response::HTTP_UNAUTHORIZED
        );
    }

    private function sendRequestWithExpectedHeaders(
        string $targetId,
        array $requestPayload,
        InstitutionUser $actingUser,
        array $tolkevaravClaimsOverride = []): TestResponse
    {
        $jwt = AuthHelpers::generateAccessTokenForInstitutionUser($actingUser, $tolkevaravClaimsOverride);

        return $this->sendRequestWithCustomHeaders(
            $targetId,
            $requestPayload,
            ['Authorization' => 'Bearer '.$jwt, 'X-Request-Id' => static::TRACE_ID]
        );
    }

    private function sendRequestWithCustomHeaders(
        string $targetId,
        array $requestPayload,
        array $headers): TestResponse
    {
        return $this
            ->withHeaders($headers)
            ->putJson("/api/institution-users/$targetId", $requestPayload);
    }

    private function assertInstitutionUserUnchangedAfterAction(Closure $action, InstitutionUser $institutionUser, int $expectedResponseStatus): void
    {
        $this->assertModelsWithoutChangeAfterAction(
            $action,
            RepresentationHelpers::createInstitutionUserNestedRepresentation(...),
            [$institutionUser],
            $expectedResponseStatus
        );
    }

    private static function createExampleValidPayload(): array
    {
        return [
            'user' => [
                'forename' => 'Exampleforename',
                'surname' => 'Examplesurname',
            ],
            'email' => 'example-email@singleton.ee',
            'phone' => '+372 45678901',
            'roles' => [],
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
        ];
    }

    /**
     * @param  Closure(Institution):void|null  $modifyStateUsingInstitution
     * @param  Closure(InstitutionUser):void|null  $modifyStateUsingActingUser
     * @param  Closure(InstitutionUser):void|null  $modifyStateUsingTargetUser
     * @return array{
     *     institution: Institution,
     *     actingInstitutionUser: InstitutionUser,
     *     targetInstitutionUser: InstitutionUser
     * }
     *
     * @throws Throwable
     */
    private function createInstitutionTargetUserAndPrivilegedActingUser(
        Closure $modifyStateUsingInstitution = null,
        Closure $modifyStateUsingActingUser = null,
        Closure $modifyStateUsingTargetUser = null,
    ): array {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
        ] = $this->createInstitutionAndActingUser(
            $modifyStateUsingInstitution,
            function (InstitutionUser $institutionUser) use ($modifyStateUsingActingUser) {
                $institutionUser->roles()->attach(
                    Role::factory()
                        ->hasAttached([
                            Privilege::firstWhere('key', PrivilegeKey::EditUser->value),
                            Privilege::firstWhere('key', PrivilegeKey::EditUserWorktime->value),
                        ])
                        ->create()
                );

                if (filled($modifyStateUsingActingUser)) {
                    $modifyStateUsingActingUser($institutionUser);
                }
            }
        );

        $targetInstitutionUser = InstitutionUser::factory()->for($institution)->create();

        if (filled($modifyStateUsingTargetUser)) {
            $modifyStateUsingTargetUser($targetInstitutionUser);
            $targetInstitutionUser->saveOrFail();
        }

        return [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetInstitutionUser' => $targetInstitutionUser,
        ];
    }

    private static function createNullWorktimeIntervals(): array
    {
        return [
            'monday_worktime_start' => null,
            'monday_worktime_end' => null,
            'tuesday_worktime_start' => null,
            'tuesday_worktime_end' => null,
            'wednesday_worktime_start' => null,
            'wednesday_worktime_end' => null,
            'thursday_worktime_start' => null,
            'thursday_worktime_end' => null,
            'friday_worktime_start' => null,
            'friday_worktime_end' => null,
            'saturday_worktime_start' => null,
            'saturday_worktime_end' => null,
            'sunday_worktime_start' => null,
            'sunday_worktime_end' => null,
        ];
    }

    /**
     * @param  InstitutionUser  $institutionUserBeforeRequest
     * @param  Closure(array): void  $assertOnEventParameters
     */
    private function assertMessageRepresentsInstitutionUserModification(array $actualMessageBody, array $institutionUserBeforeRequest, InstitutionUser $actingUser, Closure $assertOnEventParameters): void
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

        Assertions::assertArrayHasSubsetIgnoringOrder(
            $expectedMessageBodySubset,
            collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
        );

        $eventParameters = data_get($actualMessageBody, 'event_parameters');
        $this->assertIsArray($eventParameters);

        $expectedEventParametersSubset = [
            'object_type' => AuditLogEventObjectType::InstitutionUser->value,
            'object_identity_subset' => [
                ...Arr::only($institutionUserBeforeRequest, 'id'),
                'user' => Arr::only($institutionUserBeforeRequest['user'], ['id', 'forename', 'surname', 'personal_identification_code']),
            ],
        ];
        Assertions::assertArraysEqualIgnoringOrder(
            $expectedEventParametersSubset,
            collect($eventParameters)->intersectByKeys($expectedEventParametersSubset)->all(),
        );

        $assertOnEventParameters($eventParameters);
    }
}
