<?php

use Database\Helpers\RootRoleAwareInsertPrivilegesMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const OLD_KEYS = [
        'VIEW_EXTERNAL_TRANSLATION_REQUEST',
        'RESPOND_EXTERNAL_TRANSLATION_REQUEST',
        'MANAGE_EXTERNAL_TRANSLATION_REQUEST',
    ];

    private const NEW_KEYS = [
        'VIEW_OUTSOURCE_REQUEST',
        'RESPOND_OUTSOURCE_REQUEST',
        'MANAGE_OUTSOURCE_REQUEST',
    ];

    public function up(): void
    {
        $oldIds = DB::table('privileges')->whereIn('key', self::OLD_KEYS)->pluck('id');
        DB::table('privilege_roles')->whereIn('privilege_id', $oldIds)->delete();
        DB::table('privileges')->whereIn('id', $oldIds)->delete();
        DB::table('privileges')->insert(
            array_map(fn(string $key) => [
                'id'         => Str::orderedUuid(),
                'key'        => $key,
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
            ], self::NEW_KEYS)
        );
        RootRoleAwareInsertPrivilegesMigration::populateRootRolesWithAllPrivileges();
    }

    public function down(): void
    {
        $newIds = DB::table('privileges')->whereIn('key', self::NEW_KEYS)->pluck('id');
        DB::table('privilege_roles')->whereIn('privilege_id', $newIds)->delete();
        DB::table('privileges')->whereIn('id', $newIds)->delete();
        DB::table('privileges')->insert(
            array_map(fn(string $key) => [
                'id'         => Str::orderedUuid(),
                'key'        => $key,
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
            ], self::OLD_KEYS)
        );
        RootRoleAwareInsertPrivilegesMigration::populateRootRolesWithAllPrivileges();
    }
};
