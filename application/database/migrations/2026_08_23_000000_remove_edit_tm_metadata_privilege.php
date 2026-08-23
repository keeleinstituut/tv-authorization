<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const KEY = 'EDIT_TM_METADATA';

    public function up(): void
    {
        $id = DB::table('privileges')->where('key', self::KEY)->value('id');
        DB::table('privilege_roles')->where('privilege_id', $id)->delete();
        DB::table('privileges')->where('id', $id)->delete();
        RootRoleAwareInsertPrivilegesMigration::populateRootRolesWithAllPrivileges();
    }

    public function down(): void
    {
        DB::table('privileges')->insert([
            'id'         => Str::orderedUuid(),
            'key'        => self::KEY,
            'created_at' => DB::raw('NOW()'),
            'updated_at' => DB::raw('NOW()'),
        ]);
        RootRoleAwareInsertPrivilegesMigration::populateRootRolesWithAllPrivileges();
    }
};
