<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Feature\DepartmentHelpers;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;
use Throwable;

abstract class DepartmentControllerTestCase extends TestCase
{
    use ModelAssertions, RefreshDatabase;

    /**
     * @param  Closure(InstitutionUser):void|null  $modifyActingInstitutionUser
     * @param  Closure(Department):void|null  ...$departmentModifiers
     * @return array{
     *     institution: Institution,
     *     actingInstitutionUser: InstitutionUser,
     *     departments: Collection<Department>
     * }
     *
     * @throws Throwable
     */
    public static function createDepartmentsAndActingUserInSameInstitution(Closure $modifyActingInstitutionUser = null,
        ?Closure ...$departmentModifiers): array
    {
        $institution = Institution::factory()->create();
        $actingInstitutionUser = InstitutionUser::factory()->for($institution)->create();
        $departments = DepartmentHelpers::createModifiableDepartmentsInSameInstitution($institution, ...$departmentModifiers);

        if (filled($modifyActingInstitutionUser)) {
            $modifyActingInstitutionUser($actingInstitutionUser);
            $actingInstitutionUser->saveOrFail();
        }

        return [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser,
            'departments' => $departments,
        ];
    }

    public function assertInstitutionDepartmentsUnchangedAfterAction(Closure $action,
        int $expectedResponseStatus,
        Institution $institution,
        Department ...$otherDepartments): void
    {
        $institutionDepartmentsBeforeRequest = $institution->departments()->count();

        $this->assertDepartmentsUnchangedAfterAction(
            $action,
            $expectedResponseStatus,
            ...$institution->departments->all(),
            ...$otherDepartments
        );

        $institutionDepartmentsAfterRequest = $institution->refresh()->departments()->count();
        $this->assertEquals($institutionDepartmentsBeforeRequest, $institutionDepartmentsAfterRequest);
    }

    public function assertDepartmentsUnchangedAfterAction(Closure $action,
        int $expectedResponseStatus,
        Department ...$departments): void
    {
        $institutionUsersByDepartmentBeforeRequest = collect($departments)
            ->mapWithKeys(fn (Department $department) => [
                $department->id => $department
                    ->institutionUsers
                    ->map(fn (Model $model) => $model->toArray())
                    ->all(),
            ]);
        $defaultInclusionByDepartmentBeforeRequest = collect($departments)
            ->mapWithKeys(fn (Department $department) => [
                $department->id => Department::find($department->id) === null,
            ]);

        $this->assertModelsWithoutChangeAfterAction(
            $action,
            RepresentationHelpers::createDepartmentFlatRepresentation(...),
            $departments,
            $expectedResponseStatus
        );

        $institutionUsersByDepartmentAfterRequest = collect($departments)
            ->mapWithKeys(fn (Department $department) => [
                $department->id => $department->refresh()
                    ->institutionUsers
                    ->map(fn (Model $model) => $model->toArray())
                    ->all(),
            ]);

        $defaultInclusionByDepartmentAfterRequest = collect($departments)
            ->mapWithKeys(fn (Department $department) => [
                $department->id => Department::find($department->id) === null,
            ]);

        $this->assertEquals($institutionUsersByDepartmentBeforeRequest, $institutionUsersByDepartmentAfterRequest);
        $this->assertEquals($defaultInclusionByDepartmentBeforeRequest, $defaultInclusionByDepartmentAfterRequest);
    }
}
