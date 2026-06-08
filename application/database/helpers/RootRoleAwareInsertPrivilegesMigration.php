<?php

namespace Database\Helpers;

use App\Enums\InstitutionType;
use App\Enums\PrivilegeKey;
use App\Models\Privilege;
use App\Models\Role;

abstract class RootRoleAwareInsertPrivilegesMigration extends InsertPrivilegesMigration
{
    public function up(): void
    {
        parent::up();
        static::populateRootRolesWithAllPrivileges();
    }

    public static function populateRootRolesWithAllPrivileges(): void
    {
        $allPrivileges = Privilege::all(['id', 'key']);
        $rootRoles = Role::where('is_root', true)->with('institution')->get();

        $translationAgencyPrivilegeIds = $allPrivileges->whereIn('key', PrivilegeKey::TRANSLATION_AGENCY_ALLOWED_PRIVILEGES)
            ->pluck('id');
        $allPrivilegeIds = $allPrivileges->pluck('id');

        $rootRoles->each(function (Role $role) use ($allPrivilegeIds, $translationAgencyPrivilegeIds) {
            $privilegeIds = $role->institution?->type === InstitutionType::TranslationAgency
                ? $translationAgencyPrivilegeIds
                : $allPrivilegeIds;
            $role->privileges()->sync($privilegeIds);
        });
    }
}
