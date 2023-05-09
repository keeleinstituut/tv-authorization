<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Support\Arr;

class RepresentationHelpers
{
    public static function createInstitutionFlatRepresentation(Institution $institution): array
    {
        return Arr::only($institution->toArray(), [
            'id',
            'name',
            'logo_url',
            'updated_at',
            'created_at',
            'short_name',
            'phone',
            'email',
        ]);
    }
}
