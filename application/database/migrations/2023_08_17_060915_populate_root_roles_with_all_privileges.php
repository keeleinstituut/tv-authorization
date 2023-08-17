<?php

use Database\Helpers\InsertPrivilegesMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        InsertPrivilegesMigration::populateRootRolesWithAllPrivileges();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
