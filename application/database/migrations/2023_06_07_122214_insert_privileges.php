<?php

use Database\Helpers\InsertPrivilegesWithUpdateRootRoleMigration;

return new class extends InsertPrivilegesWithUpdateRootRoleMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'ADD_TAG',
            'EDIT_TAG',
            'DELETE_TAG',
        ];
    }
};
