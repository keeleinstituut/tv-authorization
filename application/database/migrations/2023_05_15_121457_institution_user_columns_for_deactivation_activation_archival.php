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
        Schema::table('institution_users', function (Blueprint $table) {
            $table->dropColumn(['status']);
            $table->date('deactivation_date')->nullable();
            $table->timestampTz('archived_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_users', function (Blueprint $table) {
            $table->string('status', 20);
            $table->dropColumn(['deactivation_date', 'archived_at']);
        });

        DB::statement(
            'ALTER TABLE institution_users '.
            'ADD CONSTRAINT institution_users_status_check '.
            "CHECK (status IN ('CREATED', 'ACTIVATED', 'DEACTIVATED'))"
        );
    }
};
