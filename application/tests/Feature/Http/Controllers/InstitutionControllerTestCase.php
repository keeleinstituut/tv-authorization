<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Institution;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;
use Tests\MockedAmqpPublisherTestCase;

abstract class InstitutionControllerTestCase extends MockedAmqpPublisherTestCase
{
    use RefreshDatabase, ModelAssertions;

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
