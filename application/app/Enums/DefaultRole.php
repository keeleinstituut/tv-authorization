<?php

namespace App\Enums;

enum DefaultRole: string
{
    case InstitutionAdmin = 'Asutuse peakasutaja';

    public static function privileges(DefaultRole $role): array
    {
        return match ($role) {
            DefaultRole::InstitutionAdmin => [
                PrivilegeKey::AddRole,
                PrivilegeKey::ViewRole,
                PrivilegeKey::EditRole,
                PrivilegeKey::DeleteRole,
                PrivilegeKey::AddUser,
                PrivilegeKey::EditUser,
                PrivilegeKey::ViewUser,
                PrivilegeKey::ExportUser,
                PrivilegeKey::ActivateUser,
                PrivilegeKey::DeactivateUser,
                PrivilegeKey::ArchiveUser,
                PrivilegeKey::EditUserWorkTime,
                PrivilegeKey::EditUserVacation,
            ],
            default => []
        };
    }
}
