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
        Schema::create('institution_user_vacations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_user_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['institution_user_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_user_vacations');
    }
};
