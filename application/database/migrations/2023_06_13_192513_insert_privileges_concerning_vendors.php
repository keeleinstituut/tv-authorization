<?php

use Database\Helpers\InsertPrivilegesMigration;

return new class extends InsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'VIEW_VENDOR_DB',
            'EDIT_VENDOR_DB',
            'VIEW_INSTITUTION_PRICE_RATE',
            'EDIT_INSTITUTION_PRICE_RATE',
            'VIEW_GENERAL_PRICELIST',
            'VIEW_VENDOR_TASK',
        ];
    }
};
