<?php

namespace App\Enums;

enum DefaultRole: string
{
    case InstitutionAdmin = 'Asutuse peakasutaja';

    public static function privileges(DefaultRole $role): array
    {
        return match ($role) {
            DefaultRole::InstitutionAdmin => PrivilegeKey::cases(),
            default => []
        };
    }
}
