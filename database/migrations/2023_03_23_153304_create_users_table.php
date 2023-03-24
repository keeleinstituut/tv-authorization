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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->softDeletesTz();
            $table->timestampsTz();
            $table->text('forename');
            $table->text('surname');
            $table->text('personal_identification_code')->unique();
            $table->comment(
                'Table storing physical persons, referred to as users. '.
                'Users belong to one or more institutions.'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
