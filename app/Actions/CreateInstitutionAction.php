<?php

namespace App\Actions;

use App\Models\Institution;

class CreateInstitutionAction
{
    public function execute(string $name): Institution
    {
        return Institution::create([
            'name' => $name,
        ]);
    }
}
