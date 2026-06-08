<?php

namespace App\Enums;

enum DefaultRole: string
{
    case InstitutionAdmin = 'Asutuse peakasutaja';
    case TranslationAgencyAdmin = 'Tõlkebüroo peamine kasutaja';

    public static function privileges(DefaultRole $role): array
    {
        return match ($role) {
            DefaultRole::InstitutionAdmin => PrivilegeKey::cases(),
            DefaultRole::TranslationAgencyAdmin => PrivilegeKey::TRANSLATION_AGENCY_ALLOWED_PRIVILEGES
        };
    }
}
