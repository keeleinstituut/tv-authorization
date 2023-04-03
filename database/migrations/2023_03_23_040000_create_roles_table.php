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
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignUuid('institution_id')->constrained();
            $table->text('name');
            $table->unique(['institution_id', 'name']);
            $table->comment(
                'Roles are a grouping of privileges. '.
                'Roles always belong to an institution. '.
                'Roles are not meant to be used for authorization directly.'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
