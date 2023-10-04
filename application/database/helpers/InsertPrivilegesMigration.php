<?php

namespace Database\Helpers;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class InsertPrivilegesMigration extends Migration
{
    public function up(): void
    {
        DB::table('privileges')->insert(
            array_map(function (string $key) {
                return [
                    'id' => Str::orderedUuid(),
                    'key' => $key,
                    'created_at' => DB::raw('NOW()'),
                    'updated_at' => DB::raw('NOW()'),
                ];
            }, $this->getPrivilegesKeys())
        );
    }

    public function down(): void
    {
        DB::table('privileges')
            ->whereIn('key', $this->getPrivilegesKeys())
            ->delete();
    }

    /**
     * @return string[]
     */
    abstract public function getPrivilegesKeys(): array;
}
