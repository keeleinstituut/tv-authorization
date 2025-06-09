<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;

return new class extends RootRoleAwareInsertPrivilegesMigration
{

    public function getPrivilegesKeys(): array
    {
        return [
            'CHANGE_PROJECT_MANAGER',
        ];
    }
};
