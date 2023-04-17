<?php

namespace Feature\Actions;

use App\Actions\CreateInstitutionAction;
use App\DataTransferObjects\InstitutionData;
use Tests\TestCase;

class CreateInstitutionActionTest extends TestCase
{
    public function test_institution_creation(): void
    {
        $institutionName = 'institution name';
        $logoUrl = 'https://some-domain.com/logo.svg';
        $institution = (new CreateInstitutionAction())->execute(
            new InstitutionData(
                $institutionName,
                $logoUrl
            )
        );

        $this->assertModelExists($institution);
        $this->assertEquals($institutionName, $institution->name);
        $this->assertEquals($logoUrl, $institution->logo_url);
    }
}
