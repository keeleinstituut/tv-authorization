<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\DepartmentController;
use App\Http\RouteConstants;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class DepartmentControllerUpdateTest extends DepartmentControllerTestCase
{
    /** @return array<array{
     *     modifyTargetDepartment: Closure(Department):void,
     *     nameToSet: string,
     *     createAdditionalDepartments: Closure(Institution):void
     * }> */
    public static function provideExistingStateModifiersAndCorrespondingValidNewNames(): array
    {
        return [
            'No other departments exist' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Some name']);
                },
                'createAdditionalDepartments' => fn () => null,
                'nameToSet' => 'Another name',
            ],
            'No other departments exist; target department has member users' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Some name']);
                    InstitutionUser::factory(3)
                        ->for($department->institution)
                        ->for($department)
                        ->create();
                },
                'createAdditionalDepartments' => fn () => null,
                'nameToSet' => 'Another name',
            ],
            'Exists department with another name without having members' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Some name']);
                },
                'createAdditionalDepartments' => function (Institution $institution) {
                    Department::factory()
                        ->for($institution)
                        ->create(['name' => 'Another name']);
                },
                'nameToSet' => 'New name',
            ],
            'Exists department with another name having members' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Some name']);
                },
                'createAdditionalDepartments' => function (Institution $institution) {
                    Department::factory()
                        ->has(InstitutionUser::factory(3)->for($institution))
                        ->for($institution)
                        ->create(['name' => 'Another name']);
                },
                'nameToSet' => 'New name',
            ],
            'Exists department with same name, but soft-deleted' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Some name']);
                },
                'createAdditionalDepartments' => function (Institution $institution) {
                    Department::factory()
                        ->for($institution)
                        ->create(['name' => 'Another name'])
                        ->deleteOrFail();
                },
                'nameToSet' => 'Another name',
            ],
            'Exists department with same name in another institution' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Some name']);
                },
                'createAdditionalDepartments' => function () {
                    Department::factory()
                        ->for(Institution::factory())
                        ->create(['name' => 'Another name']);
                },
                'nameToSet' => 'Another name',
            ],
        ];
    }

    /** @dataProvider provideExistingStateModifiersAndCorrespondingValidNewNames
     * @param  Closure(Department):void  $modifyTargetDepartment
     * @param  Closure(Institution):void  $createAdditionalDepartments
     *
     * @throws Throwable
     */
    public function test_targeted_department_is_updated_when_no_conflict_with_existing_state(Closure $modifyTargetDepartment,
        Closure $createAdditionalDepartments,
        string $nameToSet): void
    {
        Date::setTestNow(Date::now());

        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetDepartment' => $targetDepartment,
            'otherDepartments' => $otherDepartments
        ] = $this->setUpFixture($modifyTargetDepartment, $createAdditionalDepartments);

        $this->assertModelsInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendUpdateRequestWithExpectedHeaders(
                $targetDepartment->id,
                self::createExpectedPayload($nameToSet),
                $actingInstitutionUser
            ),
            RepresentationHelpers::createDepartmentFlatRepresentation(...),
            [
                [$targetDepartment, ['name' => $nameToSet]],
                ...collect($otherDepartments)->map(fn ($department) => [$department, []]),
            ],
            $targetDepartment
        );
    }

    /** @return array<array{
     *     modifyTargetDepartment: Closure(Department):void,
     *     nameToSet: string,
     *     createAdditionalDepartments: Closure(Institution):void
     * }> */
    public static function provideExistingStateModifiersAndCorrespondingInvalidNewNames(): array
    {
        return [
            'Another department with same name exists with member users' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Old name']);
                    InstitutionUser::factory(3)
                        ->for($department->institution)
                        ->for($department)
                        ->create();
                },
                'createAdditionalDepartments' => function (Institution $institution) {
                    Department::factory()
                        ->has(InstitutionUser::factory(3)->for($institution))
                        ->for($institution)
                        ->create(['name' => 'Same name']);
                },
                'nameToSet' => 'Same name',
            ],
            'Another department with same name exists without member users' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Old name']);
                },
                'createAdditionalDepartments' => function (Institution $institution) {
                    Department::factory()
                        ->for($institution)
                        ->create(['name' => 'Same name']);
                },
                'nameToSet' => 'Same name',
            ],
            'New name is same as old name' => [
                'modifyTargetDepartment' => function (Department $department) {
                    $department->fill(['name' => 'Old name']);
                },
                'createAdditionalDepartments' => fn () => null,
                'nameToSet' => 'Old name',
            ],
        ];
    }

    /** @dataProvider provideExistingStateModifiersAndCorrespondingInvalidNewNames
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_existing_state_conflicts_with_request(Closure $modifyTargetDepartment,
        Closure $createAdditionalDepartments,
        string $nameToSet): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetDepartment' => $targetDepartment,
            'institution' => $institution
        ] = $this->setUpFixture($modifyTargetDepartment, $createAdditionalDepartments);

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithExpectedHeaders(
                $targetDepartment->id,
                self::createExpectedPayload($nameToSet),
                $actingInstitutionUser
            ),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $institution,
            $targetDepartment
        );
    }

    /** @return array<array{
     *    Closure(InstitutionUser):void,
     *    int
     * }> */
    public static function provideActingUserInvalidatorsAndExpectedResponseStatus(): array
    {
        return [
            'Acting institution user with all privileges except EDIT_DEPARTMENT' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->sync(
                        Role::factory()
                            ->hasAttached(Privilege::where('key', '!=', PrivilegeKey::EditDepartment->value)->get())
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
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetDepartment' => $targetDepartment,
            'institution' => $institution
        ] = $this->setUpFixture(
            modifyTargetDepartment: function (Department $department) {
                $department->fill(['name' => 'Old name']);
            },
            modifyActingInstitutionUser: $modifyActingInstitutionUser
        );

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithExpectedHeaders(
                $targetDepartment->id,
                self::createExpectedPayload('New name'),
                $actingInstitutionUser
            ),
            $expectedResponseStatus,
            $institution,
            $targetDepartment
        );
    }

    /** @return array<array{ Closure():array }> */
    public static function provideRequestPayloadInvalidators(): array
    {
        return [
            '{"name": ""}' => [fn () => ['name' => '']],
            '{"name": "\t \n}"' => [fn () => ['name' => "\t \n"]],
            '{"name": null}' => [fn () => ['name' => null]],
            '{"nimi": "Osakond"}' => [fn () => ['nimi' => 'Osakond']],
            '{"department_id": (random uuid)}' => [fn () => ['department_id' => Str::uuid()->toString()]],
            'Empty payload' => [fn () => []],
        ];
    }

    /** @dataProvider provideRequestPayloadInvalidators
     * @param  Closure():array  $createInvalidPayload
     *
     * @throws Throwable */
    public function test_nothing_is_changed_when_payload_invalid(Closure $createInvalidPayload): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetDepartment' => $targetDepartment,
            'institution' => $institution
        ] = $this->setUpFixture(
            fn (Department $department) => $department->fill(['name' => 'Old name'])
        );

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithExpectedHeaders(
                $targetDepartment->id,
                $createInvalidPayload(),
                $actingInstitutionUser
            ),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $institution,
            $targetDepartment
        );
    }

    /** @dataProvider \Tests\Feature\DataProviders::provideInvalidHeaderCreators
     * @param  Closure():array  $createHeader
     *
     * @throws Throwable */
    public function test_nothing_is_changed_when_authentication_impossible(Closure $createHeader): void
    {
        [
            'institution' => $institution,
            'targetDepartment' => $targetDepartment
        ] = $this->setUpFixture(
            fn (Department $department) => $department->fill(['name' => 'Old name'])
        );

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendUpdateRequestWithCustomHeaders(
                $targetDepartment->id,
                self::createExpectedPayload('New name'),
                $createHeader()
            ),
            Response::HTTP_UNAUTHORIZED,
            $institution
        );
    }

    public static function createExpectedPayload(string $departmentName): array
    {
        return ['name' => $departmentName];
    }

    private function sendUpdateRequestWithExpectedHeaders(mixed $departmentId, array $payload, InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendUpdateRequestWithCustomHeaders(
            $departmentId,
            $payload,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendUpdateRequestWithCustomHeaders(mixed $departmentId, array $payload, array $headers): TestResponse
    {
        return $this
            ->withHeaders($headers)
            ->putJson(
                action(
                    [DepartmentController::class, 'update'],
                    [RouteConstants::DEPARTMENT_ID => $departmentId]
                ),
                $payload
            );
    }

    /**
     * @param  Closure(Department):void|null  $modifyTargetDepartment
     * @param  Closure(Institution):void|null  $modifyAnyState
     * @param  Closure(InstitutionUser):void|null  $modifyActingInstitutionUser
     * @return array{
     *     targetDepartment: Department,
     *     otherDepartments: Collection<Department>,
     *     actingInstitutionUser: InstitutionUser,
     *     institution: Institution
     * }
     *
     * @throws Throwable
     */
    public function setUpFixture(?Closure $modifyTargetDepartment = null,
        ?Closure $modifyAnyState = null,
        ?Closure $modifyActingInstitutionUser = null): array
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'institution' => $institution,
            'departments' => $createdDepartments
        ] = $this->createDepartmentsAndActingUserInSameInstitution(
            function (InstitutionUser $institutionUser) use ($modifyActingInstitutionUser) {
                $institutionUser->roles()->attach(
                    Role::factory()
                        ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::EditDepartment->value))
                        ->create()
                );

                if (filled($modifyActingInstitutionUser)) {
                    $modifyActingInstitutionUser($institutionUser);
                }
            },
            $modifyTargetDepartment,
            fn () => null,
            fn () => null
        );

        if (filled($modifyAnyState)) {
            $modifyAnyState($institution);
        }

        $targetDepartment = $createdDepartments->first();

        $otherDepartments = $institution->departments
            ->push(...$createdDepartments)
            ->uniqueStrict('id')
            ->reject(fn (Department $department) => $targetDepartment->id === $department->id);

        return [
            'institution' => $institution,
            'targetDepartment' => $targetDepartment,
            'otherDepartments' => $otherDepartments,
            'actingInstitutionUser' => $actingInstitutionUser,
        ];
    }
}
