<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;

return new class extends RootRoleAwareInsertPrivilegesMigration
{

    public function getPrivilegesKeys(): array
    {
        return [
            'EDIT_MACHINE_TRANSLATION_SETTINGS',
            'USE_MACHINE_TRANSLATION_ETRANSLATION',
            'USE_MACHINE_TRANSLATION_AZURE_OPENAI',
        ];
    }
};
