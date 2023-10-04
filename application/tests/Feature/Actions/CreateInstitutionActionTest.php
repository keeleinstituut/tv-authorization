<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateInstitutionAction;
use App\DataTransferObjects\InstitutionData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateInstitutionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_creation(): void
    {
        $institutionName = 'institution name';
        $institutionShortName = 'INS';
        $logoUrl = 'https://some-domain.com/logo.svg';
        $institution = (new CreateInstitutionAction())->execute(
            new InstitutionData(
                $institutionName,
                $institutionShortName,
                $logoUrl
            )
        );

        $this->assertModelExists($institution);
        $this->assertEquals($institutionName, $institution->name);
        $this->assertEquals($institutionShortName, $institution->short_name);
        $this->assertEquals($logoUrl, $institution->logo_url);
    }
}
