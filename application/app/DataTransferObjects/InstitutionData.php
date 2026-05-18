<?php

namespace App\DataTransferObjects;

use App\Enums\InstitutionType;

readonly class InstitutionData
{
    public function __construct(
        public string $name,
        public string $shortName,
        public string $logoUrl,
        public InstitutionType $institutionType = InstitutionType::Institution,
    ) {
    }
}
