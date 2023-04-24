<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['institution_id', 'name']);
        });

        DB::statement(<<<'EOT'
            CREATE UNIQUE INDEX roles_name_unique ON roles (name, institution_id)
            WHERE deleted_at IS NULL
        EOT);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            // ALTER TABLE roles DROP CONSTRAINT roles_name_unique
        DB::statement(<<<'EOT'
            DROP INDEX roles_name_unique;
        EOT);

        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['institution_id', 'name']);
        });
    }
};
