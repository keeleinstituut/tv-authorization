<?php

namespace App\DataTransferObjects;

readonly class InstitutionData
{
    public function __construct(
        public string $name,
        public string $shortName,
        public string $logoUrl,
    ) {
    }
}
