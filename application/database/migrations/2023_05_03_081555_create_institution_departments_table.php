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
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignUuid('institution_id')->constrained();
            $table->text('name');
        });

        DB::statement(
            'create unique index departments_name_institution_id_unique '.
            'on departments (name, institution_id) '.
            'where (deleted_at is null)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
