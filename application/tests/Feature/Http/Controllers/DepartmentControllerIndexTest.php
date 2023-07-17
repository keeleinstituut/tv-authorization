<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\DepartmentController;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class DepartmentControllerIndexTest extends DepartmentControllerTestCase
{
    /** @return array<array{
     *     departmentModifiers: Collection<?Closure(Department):void>,
     *     expectedExcludedDepartmentIndex: ?int
     * }> */
    public static function provideDepartmentModifiersWithExpectedResponseIds(): array
    {
        return [
            'No departments' => [
                [],
                null,
            ],
            'One normal department' => [
                [null],
                null,
            ],
            'Two normal departments' => [
                [null, null],
                null,
            ],
            'Second department soft-deleted' => [
                [null, fn (Department $department) => $department->deleteOrFail()],
                1,
            ],
            'Second department in another institution' => [
                [null, fn (Department $department) => $department->institution()->associate(Institution::factory()->create())],
                1,
            ],
            'Second department has no members' => [
                [null, fn (Department $department) => $department->institutionUsers()->each(fn (InstitutionUser $institutionUser) => $institutionUser->deleteOrFail())],
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideDepartmentModifiersWithExpectedResponseIds
     *
     * @param  iterable<?Closure(Department):void>  $departmentModifiers
     *
     * @throws Throwable */
    public function test_expected_departments_listed(iterable $departmentModifiers, ?int $expectedExcludedDepartmentIndex): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'departments' => $departments
        ] = $this->createDepartmentsAndActingUserInSameInstitution(null, ...$departmentModifiers);

        $expectedResponseData = $departments->when(
            $expectedExcludedDepartmentIndex !== null,
            fn (Collection $collection) => $collection->except($expectedExcludedDepartmentIndex))
            ->map(RepresentationHelpers::createDepartmentFlatRepresentation(...))
            ->all();

        $response = $this->sendIndexRequestWithExpectedHeaders($actingInstitutionUser);

        $this->assertResponseJsonDataEqualsIgnoringOrder($expectedResponseData, $response);
        $response->assertOk();
    }

    /** @dataProvider \Tests\Feature\DataProviders::provideInvalidHeaderCreators
     * @param  Closure():array  $createHeader
     *
     * @throws Throwable */
    public function test_401_when_not_authenticated(Closure $createHeader): void
    {
        $this->createDepartmentsAndActingUserInSameInstitution(null, null);
        $response = $this->sendIndexRequestWithCustomHeaders($createHeader());
        $response->assertUnauthorized();
    }

    private function sendIndexRequestWithExpectedHeaders(InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendIndexRequestWithCustomHeaders(
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendIndexRequestWithCustomHeaders(array $headers): TestResponse
    {
        return $this
            ->withHeaders($headers)
            ->getJson(action([DepartmentController::class, 'index']));
    }
}
