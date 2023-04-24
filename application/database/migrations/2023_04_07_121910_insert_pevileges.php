<?php

use Database\Helpers\InsertPrivilegesMigration;

return new class extends InsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            'SET_USER_WORKTIME',
            'SET_USER_VACATION',
        ];
    }
};
