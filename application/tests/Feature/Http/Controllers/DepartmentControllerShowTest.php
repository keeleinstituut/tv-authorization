<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\DepartmentController;
use App\Http\RouteConstants;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class DepartmentControllerShowTest extends DepartmentControllerTestCase
{
    /** @return array<array{
     *     departmentModifier: ?Closure(Department):void,
     *     expectedResponseStatus: int
     * }> */
    public static function provideDepartmentModifiersExpectedResponseStatus(): array
    {
        return [
            'Normal department' => [
                null,
                Response::HTTP_OK,
            ],
            'Department without members' => [
                fn (Department $department) => $department->institutionUsers()->delete(),
                Response::HTTP_OK,
            ],
            'Department soft-deleted' => [
                fn (Department $department) => $department->deleteOrFail(),
                Response::HTTP_NOT_FOUND,
            ],
            'Department in another institution' => [
                fn (Department $department) => $department->institution()->associate(Institution::factory()->create()),
                Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /** @dataProvider provideDepartmentModifiersExpectedResponseStatus
     * @param  ?Closure(Department):void  $modifyDepartment
     *
     * @throws Throwable
     */
    public function test_expected_response_returned_for_target_department(?Closure $modifyDepartment, int $expectedResponseStatus): void
    {
        [
            'actingInstitutionUser' => $actingInstitutionUser,
            'departments' => [$targetDepartment]
        ] = $this->createDepartmentsAndActingUserInSameInstitution(null, $modifyDepartment, null);

        $response = $this->sendShowRequestWithExpectedHeaders($targetDepartment->id, $actingInstitutionUser);

        if ($expectedResponseStatus === Response::HTTP_OK) {
            $response->assertOk()->assertJson([
                'data' => RepresentationHelpers::createDepartmentFlatRepresentation($targetDepartment),
            ]);
        } else {
            $response->assertStatus($expectedResponseStatus);
        }
    }

    /** @dataProvider \Tests\Feature\DataProviders::provideInvalidHeaderCreators
     * @param  Closure():array  $createHeader
     *
     * @throws Throwable */
    public function test_401_when_not_authenticated(Closure $createHeader): void
    {
        [
            'departments' => [$targetDepartment]
        ] = $this->createDepartmentsAndActingUserInSameInstitution(null, null);
        $response = $this->sendShowRequestWithCustomHeaders($targetDepartment->id, $createHeader());
        $response->assertUnauthorized();
    }

    private function sendShowRequestWithExpectedHeaders(mixed $departmentId, InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendShowRequestWithCustomHeaders(
            $departmentId,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendShowRequestWithCustomHeaders(mixed $departmentId, array $headers): TestResponse
    {
        return $this
            ->withHeaders($headers)
            ->getJson(action(
                [DepartmentController::class, 'show'],
                [RouteConstants::DEPARTMENT_ID => $departmentId]
            ));
    }
}
