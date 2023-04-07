<?php

namespace Feature\Actions;

use App\Actions\CreateInstitutionAction;
use Tests\TestCase;

class CreateInstitutionActionTest extends TestCase
{
    public function test_institution_creation(): void
    {
        $institutionName = 'institution name';
        $institution = (new CreateInstitutionAction())->execute($institutionName);

        $this->assertModelExists($institution);
        $this->assertEquals($institutionName, $institution->name);
    }
}
