<?php

use Database\Helpers\InsertPrivilegesMigration;

return new class extends InsertPrivilegesMigration
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
