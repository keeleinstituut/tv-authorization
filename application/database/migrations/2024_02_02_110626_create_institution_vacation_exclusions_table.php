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
        Schema::create('institution_vacation_exclusions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_user_id')->constrained();
            $table->foreignUuid('institution_vacation_id')->constrained();
            $table->timestampsTz();

            $table->unique(['institution_user_id', 'institution_vacation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_vacation_exclusions');
    }
};
