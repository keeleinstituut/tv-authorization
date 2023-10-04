<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;

return new class extends RootRoleAwareInsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'CREATE_PROJECT',
            'MANAGE_PROJECT',
            'RECEIVE_AND_MANAGE_PROJECT',
            'VIEW_PERSONAL_PROJECT',
            'VIEW_INSTITUTION_PROJECT_LIST',
            'VIEW_INSTITUTION_PROJECT_DETAIL',
            'CHANGE_CLIENT',
        ];
    }
};
