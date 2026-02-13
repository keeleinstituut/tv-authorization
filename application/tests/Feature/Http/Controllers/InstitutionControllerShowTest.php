<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\InstitutionController;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelpers;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class InstitutionControllerShowTest extends InstitutionControllerTestCase
{
    /** @return array<array{
     *     ?Closure(Institution):void,
     *     ?Closure(InstitutionUser):void,
     *     int,
     *     bool
     * }> */
    public static function provideInstitutionModifiersExpectedResponseStatus(): array
    {
        return [
            'Default institution' => [
                null,
                null,
                Response::HTTP_OK,
                false, // isPublicRepresentation
            ],
            'Institution with short name' => [
                fn (Institution $institution) => $institution->fill(['short_name' => 'ÜKS']),
                null,
                Response::HTTP_OK,
                false,
            ],
            'Institution with departments' => [
                fn (Institution $institution) => Department::factory(3)->for($institution)->create(),
                null,
                Response::HTTP_OK,
                false,
            ],
            'Soft-deleted institution' => [
                fn (Institution $institution) => $institution->deleteOrFail(),
                null,
                Response::HTTP_NOT_FOUND,
                false,
            ],
            'Acting user belongs to another institution' => [
                null,
                fn (InstitutionUser $institutionUser) => $institutionUser->institution()->associate(Institution::factory()->create()),
                Response::HTTP_OK,
                true, // isPublicRepresentation
            ],
        ];
    }

    /** @param  ?Closure(Institution):void  $modifyInstitution
     * @param  ?Closure(InstitutionUser):void  $modifyActingInstitutionUser
     *
     * @throws Throwable
     */
    #[DataProvider('provideInstitutionModifiersExpectedResponseStatus')]
    public function test_expected_response_returned_for_institution(
        ?Closure $modifyInstitution,
        ?Closure $modifyActingInstitutionUser,
        int $expectedResponseStatus,
        bool $isPublicRepresentation): void
    {
        [
            'institution' => $institution,
            'actingInstitutionUser' => $actingInstitutionUser
        ] = static::createInstitutionAndActingUser($modifyInstitution, $modifyActingInstitutionUser);

        $response = $this->sendShowRequestWithExpectedHeaders($institution->id, $actingInstitutionUser);

        if ($expectedResponseStatus === Response::HTTP_OK) {
            if ($isPublicRepresentation) {
                $response->assertOk()->assertJson([
                    'data' => [
                        'id' => $institution->id,
                        'name' => $institution->name,
                    ],
                ]);
                // Ensure only public fields are present
                $response->assertJsonMissing(['short_name']);
                $response->assertJsonMissing(['phone']);
                $response->assertJsonMissing(['email']);
            } else {
                $response->assertOk()->assertJson([
                    'data' => RepresentationHelpers::createInstitutionFlatRepresentation($institution),
                ]);
            }
        } else {
            $response->assertStatus($expectedResponseStatus);
        }
    }

    /** @param  Closure():array  $createHeader
     *
     * @throws Throwable */
    #[DataProviderExternal('Tests\Feature\DataProviders', 'provideInvalidHeaderCreators')]
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
