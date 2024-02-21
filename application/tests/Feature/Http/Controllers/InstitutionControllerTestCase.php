<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Institution;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuditLogTestCase;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;

abstract class InstitutionControllerTestCase extends AuditLogTestCase
{
    use ModelAssertions, RefreshDatabase;

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
