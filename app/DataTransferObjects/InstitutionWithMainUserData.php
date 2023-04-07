<?php

namespace App\DataTransferObjects;

readonly class InstitutionWithMainUserData
{
    public function __construct(
        public string $institutionName,
        public UserData $userData
    ) {
    }
}
