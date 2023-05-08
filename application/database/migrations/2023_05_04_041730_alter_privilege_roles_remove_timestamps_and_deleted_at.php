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
        Schema::table('privilege_roles', function (Blueprint $table) {
            $table->dropTimestampsTz();
            $table->dropSoftDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('privilege_roles', function (Blueprint $table) {
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }
};
