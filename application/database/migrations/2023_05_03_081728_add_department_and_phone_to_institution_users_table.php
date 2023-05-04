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
            $table->string('phone')->nullable();
            $table->foreignUuid('department_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_users', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('department_id');
        });
    }
};
