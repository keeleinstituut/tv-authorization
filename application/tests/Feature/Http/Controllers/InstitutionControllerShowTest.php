<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\InstitutionController;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class InstitutionControllerShowTest extends InstitutionControllerTestCase
{
    /** @return array<array{
     *     ?Closure(Institution):void,
     *     ?Closure(InstitutionUser):void,
     *     int
     * }> */
    public static function provideInstitutionModifiersExpectedResponseStatus(): array
    {
        return [
            'Default institution' => [
                null,
                null,
                Response::HTTP_OK,
            ],
            'Institution with short name' => [
                fn (Institution $institution) => $institution->fill(['short_name' => 'ÜKS']),
                null,
                Response::HTTP_OK,
            ],
            'Institution with departments' => [
                fn (Institution $institution) => Department::factory(3)->for($institution)->create(),
                null,
                Response::HTTP_OK,
            ],
            'Soft-deleted institution' => [
                fn (Institution $institution) => $institution->deleteOrFail(),
                null,
                Response::HTTP_NOT_FOUND,
            ],
            'Acting user belongs to another institution' => [
                null,
                fn (InstitutionUser $institutionUser) => $institutionUser->institution()->associate(Institution::factory()->create()),
                Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /** @dataProvider provideInstitutionModifiersExpectedResponseStatus
     * @param  ?Closure(Institution):void  $modifyInstitution
     * @param  ?Closure(InstitutionUser):void  $modifyActingInstitutionUser
     *
     * @throws Throwable
     */
    public function test_expected_response_returned_for_institution(
        ?Closure $modifyInstitution,
        ?Closure $modifyActingInstitutionUser,
        int $expectedResponseStatus): void
    {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser
        ] = static::createInstitutionAndActingUser($modifyInstitution, $modifyActingInstitutionUser);

        $response = $this->sendShowRequestWithExpectedHeaders($institution->id, $actingInstitutionUser);

        if ($expectedResponseStatus === Response::HTTP_OK) {
            $response->assertOk()->assertJson([
                'data' => RepresentationHelpers::createInstitutionFlatRepresentation($institution),
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
            'institution' => $institution,
        ] = static::createInstitutionAndActingUser();

        $response = $this->sendShowRequestWithCustomHeaders($institution->id, $createHeader());
        $response->assertUnauthorized();
    }

    private function sendShowRequestWithExpectedHeaders(mixed $departmentId, InstitutionUser $actingInstitutionUser): TestResponse
    {
        return $this->sendShowRequestWithCustomHeaders(
            $departmentId,
            AuthHelpers::createHeadersForInstitutionUser($actingInstitutionUser)
        );
    }

    private function sendShowRequestWithCustomHeaders(mixed $institutionId, array $headers): TestResponse
    {
        return $this
            ->withHeaders($headers)
            ->getJson(action(
                [InstitutionController::class, 'show'],
                ['institution_id' => $institutionId]
            ));
    }
}
