<?php

namespace Database\Helpers;

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
        $allPrivilegeIds = Privilege::pluck('id');
        $rootRoles = Role::where('is_root', true)->get();
        $rootRoles->each(function ($role) use ($allPrivilegeIds) {
            $role->privileges()->sync($allPrivilegeIds);
        });
    }
}
