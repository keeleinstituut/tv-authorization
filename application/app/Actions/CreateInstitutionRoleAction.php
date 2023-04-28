<?php

namespace App\Actions;

use App\DataTransferObjects\InstitutionRoleData;
use App\Exceptions\EmptyRoleNameException;
use App\Exceptions\EmptyRolePrivilegesException;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateInstitutionRoleAction
{
    /**
     * @throws Throwable
     */
    public function execute(InstitutionRoleData $roleData): Role
    {
        if (empty($roleData->privilegeKeys)) {
            throw new EmptyRolePrivilegesException("Couldn't create role with empty privileges");
        }

        if (empty($roleData->name)) {
            throw new EmptyRoleNameException("Couldn't create role with empty name");
        }

        return DB::transaction(function () use ($roleData): Role {
            $role = Role::create([
                'name' => $roleData->name,
                'institution_id' => $roleData->institutionId,
            ]);

            foreach ($roleData->privilegeKeys as $privilegeKey) {
                $privilege = Privilege::where('key', $privilegeKey)->firstOrFail();
                PrivilegeRole::create([
                    'role_id' => $role->id,
                    'privilege_id' => $privilege->id,
                ]);
            }

            return $role;
        });
    }
}
