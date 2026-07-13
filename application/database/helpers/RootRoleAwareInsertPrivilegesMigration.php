<?php

namespace Database\Helpers;

use App\Enums\InstitutionType;
use App\Enums\PrivilegeKey;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

abstract class RootRoleAwareInsertPrivilegesMigration extends InsertPrivilegesMigration
{
    public function up(): void
    {
        parent::up();
        static::populateRootRolesWithAllPrivileges();
    }

    public static function populateRootRolesWithAllPrivileges(): void
    {
        $allPrivileges = DB::table('privileges')->select('id', 'key')->get();
        $rootRoles = Role::where('is_root', true)->with('institution')->get();

        $translationAgencyKeys = array_map(
            fn (PrivilegeKey $key) => $key->value,
            PrivilegeKey::TRANSLATION_AGENCY_ALLOWED_PRIVILEGES
        );
        $translationAgencyPrivilegeIds = $allPrivileges->whereIn('key', $translationAgencyKeys)
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
