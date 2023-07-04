<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use Closure;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class InstitutionControllerUpdateTest extends InstitutionControllerTestCase
{
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
            'Clearing previously filled short name' => [
                'modifyInstitution' => function (Institution $institution): void {
                    $institution->short_name = 'SHO';
                },
                'payload' => ['short_name' => null],
                'expectedStateOverride' => [],
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

    /** @dataProvider provideInstitutionModifiersAndPayloadsAndExpectedState
     * @param  null|Closure(Institution):void  $modifyInstitution
     *
     * @throws Throwable
     */
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

    /** @dataProvider provideActingUserInvalidatorsAndExpectedResponseStatus
     * @throws Throwable */
    public function test_nothing_is_changed_when_acting_user_forbidden(Closure $modifyActingInstitutionUser, int $expectedResponseStatus): void
    {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
        ] = $this->createInstitutionAndPrivilegedActingUser(modifyActingInstitutionUser: $modifyActingInstitutionUser);

        $this->assertInstitutionUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithExpectedHeaders(
                $institution->id,
                static::createExampleValidPayload(),
                $actingInstitutionUser
            ),
            $institution,
            $expectedResponseStatus
        );
    }

    /** @return array<array{array}> */
    public static function provideInvalidPayloads(): array
    {
        return [
            'name: ""' => [[...static::createExampleValidPayload(), 'name' => '']],
            'name: "\t \n"' => [[...static::createExampleValidPayload(), 'name' => "\t \n"]],
            'name: null' => [[...static::createExampleValidPayload(), 'name' => null]],
            'email: "not email"' => [[...static::createExampleValidPayload(), 'email' => 'not email']],
            'phone: "not phone"' => [[...static::createExampleValidPayload(), 'phone' => 'not phone']],
            'phone: "112"' => [[...static::createExampleValidPayload(), 'phone' => '112']],
            'phone: "512 8756"' => [[...static::createExampleValidPayload(), 'phone' => '512 8756']],
            'phone: "+4951234567"' => [[...static::createExampleValidPayload(), 'phone' => '+4951234567']],
            'short_name: "ABCD"' => [[...static::createExampleValidPayload(), 'short_name' => 'ABCD']],
        ];
    }

    /** @dataProvider provideInvalidPayloads
     *
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_payload_invalid(array $invalidPayload): void
    {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
        ] = $this->createInstitutionAndPrivilegedActingUser();

        $this->assertInstitutionUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithExpectedHeaders($institution->id, $invalidPayload, $actingInstitutionUser),
            $institution,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /** @dataProvider \Tests\Feature\DataProviders::provideInvalidHeaderCreators
     * @param  Closure():array  $createHeader
     *
     * @throws Throwable
     */
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
            ->withHeaders($headers)
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
                        ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::EditInstitution->value))
                        ->create()
                );

                if (filled($modifyActingInstitutionUser)) {
                    $modifyActingInstitutionUser($institutionUser);
                }
            }
        );
    }
}
