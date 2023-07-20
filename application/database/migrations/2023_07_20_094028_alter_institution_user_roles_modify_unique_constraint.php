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
        Schema::table('institution_user_roles', function (Blueprint $table) {
            $table->dropUnique(['institution_user_id', 'role_id']);
        });

        DB::statement(<<<'EOT'
            CREATE UNIQUE INDEX institution_user_roles_institution_user_id_role_id_unique ON institution_user_roles (institution_user_id, role_id)
            WHERE deleted_at IS NULL
        EOT);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(<<<'EOT'
            DROP INDEX institution_user_roles_institution_user_id_role_id_unique;
        EOT);

        Schema::table('institution_user_roles', function (Blueprint $table) {
            $table->unique(['institution_user_id', 'role_id']);
        });
    }
};
