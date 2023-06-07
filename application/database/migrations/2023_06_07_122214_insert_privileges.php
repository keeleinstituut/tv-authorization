<?php

use App\Enums\PrivilegeKey;
use Database\Helpers\InsertPrivilegesMigration;

return new class extends InsertPrivilegesMigration
{
    public function getPrivilegesKeys(): array
    {
        return [
            PrivilegeKey::AddTag,
            PrivilegeKey::EditTag,
            PrivilegeKey::DeleteTag
        ];
    }
};
