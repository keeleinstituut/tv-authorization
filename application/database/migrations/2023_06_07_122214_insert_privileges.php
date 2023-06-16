<?php

use App\Enums\PrivilegeKey;
use Database\Helpers\InsertPrivilegesMigration;

return new class extends InsertPrivilegesMigration
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
