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
        Schema::create('institution_user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignUuid('institution_user_id')->constrained();
            $table->foreignUuid('role_id')->constrained();
            $table->unique(['institution_user_id', 'role_id']);
            $table->comment(
                'Roles are a grouping of privileges. '.
                'Roles are not meant to be used for authorization directly.'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_user_roles');
    }
};
