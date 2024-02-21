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
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class DepartmentControllerDestroyTest extends DepartmentControllerTestCase
{
    /** @return array<array{ Closure(Department):void }> */
    public static function provideValidDepartmentModifiers(): array
    {
        return [
            'Department with members' => [function (Department $department) {
                InstitutionUser::factory(3)
                    ->for($department)
                    ->for($department->institution)
                    ->create();
            }],
            'Department without members' => [function (Department $department) {
                $department->institutionUsers()->each(fn (InstitutionUser $institutionUser) => $institutionUser->deleteOrFail());
            }],
        ];
    }

    /** @dataProvider provideValidDepartmentModifiers
     * @param  Closure(Department):void  $modifyTargetDepartment
     *
     * @throws Throwable */
    public function test_targeted_department_is_softdeleted_and_members_detached(Closure $modifyTargetDepartment): void
    {
        Date::setTestNow(Date::now());

        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetDepartment' => $targetDepartment,
            'untargetedDepartment' => $untargetedDepartment
        ] = $this->createDefaultSuccessCaseModels($modifyTargetDepartment);

        $this->assertModelsInExpectedStateAfterActionAndCheckResponseData(
            fn () => $this->sendDestroyRequestWithExpectedHeaders($targetDepartment->id, $actingInstitutionUser),
            RepresentationHelpers::createDepartmentFlatRepresentation(...),
            [[$targetDepartment, []], [$untargetedDepartment, []]],
            $targetDepartment
        );

        $this->assertNull(Department::find($targetDepartment->id));
        $this->assertSoftDeleted($targetDepartment);
        $this->assertDatabaseMissing(
            InstitutionUser::make()->getTable(),
            ['department_id' => $targetDepartment->id]
        );
        $this->assertModelExists(Department::find($untargetedDepartment->id));
    }

    /** @return array<array{
     *    Closure(InstitutionUser):void,
     *    int
     * }> */
    public static function provideActingUserInvalidatorsAndExpectedResponseStatus(): array
    {
        return [
            'Acting institution user with all privileges except DELETE_DEPARTMENT' => [
                function (InstitutionUser $institutionUser) {
                    $institutionUser->roles()->sync(
                        Role::factory()
                            ->hasAttached(Privilege::where('key', '!=', PrivilegeKey::DeleteDepartment->value)->get())
                            ->create()
                    );
                },
                Response::HTTP_FORBIDDEN,
            ],
            'Acting institution user in another institution' => [
                fn (InstitutionUser $institutionUser) => $institutionUser->institution()->associate(Institution::factory()->create()),
                Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /** @dataProvider provideActingUserInvalidatorsAndExpectedResponseStatus
     * @throws Throwable
     */
    public function test_nothing_is_changed_when_acting_user_forbidden(Closure $modifyActingInstitutionUser, int $expectedResponseStatus): void
    {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetDepartment' => $targetDepartment,
            'untargetedDepartment' => $untargetedDepartment
        ] = $this->createDefaultSuccessCaseModels(modifyActingInstitutionUser: $modifyActingInstitutionUser);

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendDestroyRequestWithExpectedHeaders($targetDepartment->id, $actingInstitutionUser),
            $expectedResponseStatus,
            $institution,
            $targetDepartment,
            $untargetedDepartment
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
            'targetDepartment' => $targetDepartment,
            'untargetedDepartment' => $untargetedDepartment
        ] = $this->createDefaultSuccessCaseModels();

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendDestroyRequestWithCustomHeaders($targetDepartment->id, $createHeader()),
            Response::HTTP_UNAUTHORIZED,
            $institution,
            $targetDepartment,
            $untargetedDepartment
        );
    }

    /** @throws Throwable */
    public function test_nothing_is_changed_when_target_already_deleted(): void
    {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
            'targetDepartment' => $targetDepartment,
            'untargetedDepartment' => $untargetedDepartment
        ] = $this->createDefaultSuccessCaseModels(
            fn (Department $department) => $department->deleteOrFail()
        );

        $this->assertInstitutionDepartmentsUnchangedAfterAction(
            fn () => $this->sendDestroyRequestWithExpectedHeaders($targetDepartment->id, $actingInstitutionUser),
            Response::HTTP_NOT_FOUND,
            $institution,
            $targetDepartment,
            $untargetedDepartment
        );
    }

    private function sendDestroyRequestWithExpectedHeaders(mixed $departmentId, InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendDestroyRequestWithCustomHeaders(
            $departmentId,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendDestroyRequestWithCustomHeaders(mixed $departmentId, array $headers): TestResponse
    {
        return $this
            ->withHeaders($headers)
            ->deleteJson(action(
                [DepartmentController::class, 'destroy'],
                ['department_id' => $departmentId]
            ));
    }

    /** @return array{
     *     targetDepartment: Department,
     *     untargetedDepartment: Department,
     *     actingInstitutionUser: InstitutionUser,
     *     institution: Institution
     * }
     *
     * @throws Throwable */
    public function createDefaultSuccessCaseModels(Closure $modifyTargetDepartment = null,
        Closure $modifyUntargetedDepartment = null,
        Closure $modifyActingInstitutionUser = null): array
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'institution' => $institution,
            'departments' => [$targetDepartment, $untargetedDepartment]
        ] = $this->createDepartmentsAndActingUserInSameInstitution(
            function (InstitutionUser $institutionUser) use ($modifyActingInstitutionUser) {
                $institutionUser->roles()->attach(
                    Role::factory()
                        ->hasAttached(Privilege::firstWhere('key', PrivilegeKey::DeleteDepartment->value))
                        ->create()
                );

                if (filled($modifyActingInstitutionUser)) {
                    $modifyActingInstitutionUser($institutionUser);
                }
            },
            $modifyTargetDepartment,
            $modifyUntargetedDepartment
        );

        return [
            'institution' => $institution,
            'targetDepartment' => $targetDepartment,
            'untargetedDepartment' => $untargetedDepartment,
            'actingInstitutionUser' => $actingInstitutionUser,
        ];
    }
}
