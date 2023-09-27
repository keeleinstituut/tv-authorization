<?php

use Database\Helpers\InsertPrivilegesWithUpdateRootRoleMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        InsertPrivilegesWithUpdateRootRoleMigration::populateRootRolesWithAllPrivileges();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
