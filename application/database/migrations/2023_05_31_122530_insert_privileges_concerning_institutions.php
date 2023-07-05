<?php

use Database\Helpers\InsertPrivilegesMigration;

return new class extends InsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'EDIT_INSTITUTION',
            'EDIT_INSTITUTION_WORKTIME',
        ];
    }
};
