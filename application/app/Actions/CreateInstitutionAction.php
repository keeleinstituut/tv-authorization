<?php

namespace App\Actions;

use App\DataTransferObjects\InstitutionData;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;

class CreateInstitutionAction
{
    public function execute(InstitutionData $institutionData): Institution
    {
        return DB::transaction(function () use ($institutionData) {
            $institution = new Institution();
            $institution->fill([
                'name' => $institutionData->name,
                'short_name' => $institutionData->shortName,
            ]);
            $institution->save();

            $institution->addMediaFromUrl($institutionData->logoUrl)
                ->toMediaCollection(Institution::LOGO_MEDIA_COLLECTION);

            return $institution;
         });
    }
}
