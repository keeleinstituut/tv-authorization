<?php

use Database\Helpers\InsertPrivilegesWithUpdateRootRoleMigration;

return new class extends InsertPrivilegesWithUpdateRootRoleMigration
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
