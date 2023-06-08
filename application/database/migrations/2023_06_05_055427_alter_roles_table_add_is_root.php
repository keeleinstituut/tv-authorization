<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_root')->default(false);
        });

        // Only one role inside institution can be marked as is_root
        DB::statement(<<<'EOT'
            CREATE UNIQUE INDEX roles_is_root_unique ON roles (is_root, institution_id)
            WHERE is_root = true
        EOT);

        // Initial roles created during Institution creation script
        // are marked as is_root.
        // Name value taken from \App\Enums\DefaultRole::InstitutionAdmin
        DB::statement(<<<'EOT'
            UPDATE roles SET is_root=true WHERE name = 'Asutuse peakasutaja'
        EOT);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(<<<'EOT'
            DROP INDEX roles_is_root_unique
        EOT);

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_root');
        });
    }
};
