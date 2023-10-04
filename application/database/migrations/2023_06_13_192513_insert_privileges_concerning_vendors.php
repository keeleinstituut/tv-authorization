<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;

return new class extends RootRoleAwareInsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'VIEW_VENDOR_DB',
            'EDIT_VENDOR_DB',
            'VIEW_GENERAL_PRICELIST',
            'VIEW_VENDOR_TASK',
        ];
    }
};
