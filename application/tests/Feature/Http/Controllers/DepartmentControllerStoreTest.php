<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\DepartmentController;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class DepartmentControllerStoreTest extends DepartmentControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Skipping all tests. Store endpoint is deprecated');
    }

    /** @return array<array{
     *    Closure(Institution):void,
     *    string
     * }> */
    public static function provideExistingStateModifiersAndCorrespondingValidNames(): array
    {
        return [
            'No other departments exist' => [
                fn (Institution $institution) => null,
                'Some name',
            ],
            'Exists department with another name without having members' => [
                fn (Institution $institution) => Department::factory()
                    ->for($institution)
                    ->create(['name' => 'Some name']),
                'Another name',
            ],
            'Exists department with another name having members' => [
                fn (Institution $institution) => Department::factory()
                    ->for($institution)
                    ->has(InstitutionUser::factory(3)->for($institution))
                    ->create(['name' => 'Some name']),
                'Another name',
            ],
            'Exists department with same name, but soft-deleted' => [
                fn (Institution $institution) => Department::factory()
                    ->for($institution)
                    ->create(['name' => 'Same name'])
                    ->deleteOrFail(),
                'Same name',
            ],
            'Exists department with same name in another institution' => [
                fn (Institution $institution) => Department::factory()
                    ->for(Institution::factory())
                    ->create(['name' => 'Same name']),
                'Same name',
            ],
        ];
    }

    /** @param  Closure(Institution):void  $modifyExistingState
     *
     * @throws Throwable
     */
    #[DataProvider('provideExistingStateModifiersAndCorrespondingValidNames')]
    public function test_expected_department_is_created_given_nonconflicting_existing_state(Closure $modifyExistingState, string $nameToCreate): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'institution' => $institution
        ] = $this->setUpFixture($modifyExistingState);
        $this->assertNull(Department::whereBelongsTo($institution)->firstWhere('name', $nameToCreate));

        $institutionDepartmentsBeforeRequest = $institution->departments()->count();
        $response = $this->sendStoreRequestWithExpectedHeaders(self::createExpectedPayload($nameToCreate), $actingInstitutionUser);

        $institutionDepartmentsAfterRequest = $institution->refresh()->departments()->count();
        $this->assertEquals($institutionDepartmentsBeforeRequest + 1, $institutionDepartmentsAfterRequest);

        $response
            ->assertCreated()
            ->assertExactJson([
                'data' => RepresentationHelpers::createDepartmentFlatRepresentation(
                    Department::firstWhere('name', $nameToCreate)
                ),
            ]);
    }

    /** @return array<array{
     *    Closure(Institution):void,
     *    string
     * }> */
    public static function provideExistingStateModifiersAndCorrespondingInvalidNames(): array
    {
        return [
            'Exists department with same name without member users' => [
                fn (Institution $institution) => Department::factory()
                    ->for($institution)
                    ->create(['name' => 'Same name']),
                'Same name',
            ],
            'Exists department with same name with member users' => [
                fn (Institution $institution) => Department::factory()
                    ->for($institution)
                    ->has(InstitutionUser::factory(3)->for($institution))
                    ->create(['name' => 'Same name']),
                'Same name',
            ],
        ];
    }

    /** @param  Closure(Institution):void  $modifyExistingState
     *
     * @throws Throwable
     */
    #[DataProvider('provideExistingStateModifiersAndCorrespondingInvalidNames')]
    public function test_nothing_is_changed_when_existing_state_conflicts_with_request(Closure $modifyExistingState, string $nameToCreate): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'institution' => $institution
        ] = $this->setUpFixture($modifyExistingState);

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendStoreRequestWithExpectedHeaders(self::createExpectedPayload($nameToCreate), $actingInstitutionUser),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $institution
        );
    }

    /** @return array<array{
     *    Closure(InstitutionUser):void,
     *    int
     * }> */
    public static function provideActingUserInvalidatorsAndExpectedResponseStatus(): array
    {
        return [
            'Acting institution user with all privileges except ADD_DEPARTMENT' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->sync(
                        Role::factory()
                            ->hasAttached(Privilege::where('key', '!=', PrivilegeKey::AddDepartment->value)->get())
                            ->create()
                    );
                },
                Response::HTTP_FORBIDDEN,
            ],
        ];
    }

    /** @throws Throwable */
    #[DataProvider('provideActingUserInvalidatorsAndExpectedResponseStatus')]
    public function test_nothing_is_changed_when_acting_user_forbidden(Closure $modifyActingInstitutionUser, int $expectedResponseStatus): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'institution' => $institution
        ] = $this->setUpFixture(modifyActingInstitutionUser: $modifyActingInstitutionUser);

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendStoreRequestWithExpectedHeaders(self::createExpectedPayload('Some name'), $actingInstitutionUser),
            $expectedResponseStatus,
            $institution
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

    /** @param  Closure():array  $createInvalidPayload
     *
     * @throws Throwable */
    #[DataProvider('provideRequestPayloadInvalidators')]
    public function test_nothing_is_changed_when_payload_invalid(Closure $createInvalidPayload): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'institution' => $institution
        ] = $this->setUpFixture();

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendStoreRequestWithExpectedHeaders($createInvalidPayload(), $actingInstitutionUser),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $institution
        );
    }

    /** @param  Closure():array  $createHeader
     *
     * @throws Throwable */
    #[DataProviderExternal('Tests\Feature\DataProviders', 'provideInvalidHeaderCreators')]
    public function test_nothing_is_changed_when_authentication_impossible(Closure $createHeader): void
    {
        ['institution' => $institution] = $this->setUpFixture();

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendStoreRequestWithCustomHeaders(self::createExpectedPayload('Some name'), $createHeader()),
            Response::HTTP_UNAUTHORIZED,
            $institution
        );
    }

    private function sendStoreRequestWithExpectedHeaders(array $requestParams, InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendStoreRequestWithCustomHeaders(
            $requestParams,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendStoreRequestWithCustomHeaders(array $requestParams, array $headers): TestResponse
    {
        return $this
            ->withHeaders($headers)
            ->postJson(
                action([DepartmentController::class, 'store']),
                $requestParams
            );
    }

    public static function createExpectedPayload(string $departmentName): array
    {
        return ['name' => $departmentName];
    }

    /**
     * @param  Closure(Institution):void|null  $modifyAnyState
     * @param  Closure(InstitutionUser):void|null  $modifyActingInstitutionUser
     * @return array{
     *     actingInstitutionUser: InstitutionUser,
     *     institution: Institution
     * }
     *
     * @throws Throwable */
    public function setUpFixture(?Closure $modifyAnyState = null,
        ?Closure $modifyActingInstitutionUser = null): array
    {
        $institution = Institution::factory()->create();
        $actingInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->has(Role::factory()->for($institution)->hasAttached(
                Privilege::firstWhere('key', PrivilegeKey::AddDepartment->value)
            ))
            ->create();

        if (filled($modifyAnyState)) {
            $modifyAnyState($institution);
        }

        if (filled($modifyActingInstitutionUser)) {
            $modifyActingInstitutionUser($actingInstitutionUser->refresh());
            $actingInstitutionUser->saveOrFail();
        }

        return [
            'actingInstitutionUser' => $actingInstitutionUser->refresh(),
            'institution' => $institution,
        ];
    }
}
