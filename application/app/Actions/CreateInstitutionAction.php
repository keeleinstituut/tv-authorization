<?php

namespace App\Actions;

use App\Models\Institution;
use App\DataTransferObjects\InstitutionData;

class CreateInstitutionAction
{
    public function execute(InstitutionData $institutionData): Institution
    {
        return Institution::create([
            'name' => $institutionData->name,
            'logo_url' => $institutionData->logoUrl,
        ]);
    }
}
