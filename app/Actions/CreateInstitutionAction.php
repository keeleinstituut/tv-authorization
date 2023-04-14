<?php

namespace App\Actions;

use App\DataTransferObjects\InstitutionData;
use App\Models\Institution;

class CreateInstitutionAction
{
    public function execute(InstitutionData $institutionData): Institution
    {
        return Institution::create([
            'name' => $institutionData->name,
            'logo_url' => $institutionData->logoUrl
        ]);
    }
}
