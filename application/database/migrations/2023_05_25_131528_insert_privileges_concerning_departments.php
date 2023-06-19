<?php

use Database\Helpers\InsertPrivilegesMigration;

return new class extends InsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'ADD_DEPARTMENT',
            'EDIT_DEPARTMENT',
            'DELETE_DEPARTMENT',
        ];
    }
};
