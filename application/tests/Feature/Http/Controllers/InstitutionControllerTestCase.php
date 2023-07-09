<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;
use Tests\TestCase;
use Throwable;

abstract class InstitutionControllerTestCase extends TestCase
{
    use RefreshDatabase, ModelAssertions;

    /**
     * @param  Closure(Institution):void|null  $modifyInstitution
     * @param  Closure(InstitutionUser):void|null  $modifyActingInstitutionUser
     * @return array{
     *     institution: Institution,
     *     actingInstitutionUser: InstitutionUser,
     * }
     *
     * @throws Throwable
     */
    public static function createInstitutionAndActingUser(
        ?Closure $modifyInstitution = null,
        ?Closure $modifyActingInstitutionUser = null): array
    {
        $institution = Institution::factory()->create();
        $actingInstitutionUser = InstitutionUser::factory()->for($institution)->create();

        if (filled($modifyInstitution)) {
            $modifyInstitution($institution);
            $institution->saveOrFail();
        }

        if (filled($modifyActingInstitutionUser)) {
            $modifyActingInstitutionUser($actingInstitutionUser);
            $actingInstitutionUser->saveOrFail();
        }

        return [
            'institution' => $institution->refresh(),
            'actingInstitutionUser' => $actingInstitutionUser->refresh(),
        ];
    }

    public function assertInstitutionUnchangedAfterAction(Closure $action,
        Institution $institution,
        int $expectedResponseStatus): void
    {
        $this->assertModelsWithoutChangeAfterAction(
            $action,
            RepresentationHelpers::createInstitutionFlatRepresentation(...),
            [$institution],
            $expectedResponseStatus
        );
    }
}
