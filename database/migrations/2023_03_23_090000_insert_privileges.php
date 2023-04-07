<?php


use Database\Helpers\InsertPrivilegesMigration;

return new class extends InsertPrivilegesMigration
{
    function getPrivilegesKeys(): array
    {
        return [
            'ADD_ROLE',
            'VIEW_ROLE',
            'EDIT_ROLE',
            'DELETE_ROLE',
            'ADD_USER',
            'EDIT_USER',
            'VIEW_USER',
            'EXPORT_USER',
            'ACTIVATE_USER',
            'DEACTIVATE_USER',
            'ARCHIVE_USER',
        ];
    }
};
