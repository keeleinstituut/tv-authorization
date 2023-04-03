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
        Schema::create('institution_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignUuid('institution_id')->constrained();
            $table->foreignUuid('user_id')->constrained();
            $table->string('status', 20);
            $table->unique(['institution_id', 'user_id']);
        });

        DB::statement(
            'ALTER TABLE institution_users '.
            'ADD CONSTRAINT institution_users_status_check '.
            "CHECK (status IN ('CREATED', 'ACTIVATED', 'DEACTIVATED'))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_users');
    }
};
