<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;

return new class extends RootRoleAwareInsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'VIEW_INSTITUTION_PRICELIST',
            'EDIT_INSTITUTION_PRICELIST',
            'VIEW_EXTERNAL_PARTNER',
            'MANAGE_EXTERNAL_PARTNER',
            'VIEW_EXTERNAL_TRANSLATION_REQUEST',
            'RESPOND_EXTERNAL_TRANSLATION_REQUEST',
            'MANAGE_EXTERNAL_TRANSLATION_REQUEST',
        ];
    }
};
