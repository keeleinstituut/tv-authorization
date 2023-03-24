<?php

use App\Models\Institution;
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
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignIdFor(Institution::class);
            $table->text('name');
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
