<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;

return new class extends RootRoleAwareInsertPrivilegesMigration
{

    public function getPrivilegesKeys(): array
    {
        return [
            'EDIT_AUDIT_LOG_SETTINGS',
        ];
    }
};
