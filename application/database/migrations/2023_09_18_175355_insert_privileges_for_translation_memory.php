<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;

return new class extends RootRoleAwareInsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'IMPORT_TM',
            'CREATE_TM',
            'EXPORT_TM',
            'EDIT_TM_METADATA',
            'EDIT_TM',
            'DELETE_TM',
            'VIEW_TM',
        ];
    }
};
