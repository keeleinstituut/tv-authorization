<?php

namespace App\DataTransferObjects;

readonly class UserData
{
    public function __construct(
        public string $pin,
        public string $email,
        public string $surname,
        public string $forename,
    ) {
    }
}
