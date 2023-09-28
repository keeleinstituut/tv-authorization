<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        RootRoleAwareInsertPrivilegesMigration::populateRootRolesWithAllPrivileges();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
