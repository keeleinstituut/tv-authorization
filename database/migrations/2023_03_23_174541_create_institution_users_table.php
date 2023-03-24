<?php

use App\Models\Institution;
use App\Models\InstitutionUserStatus;
use App\Models\User;
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
        Schema::create('institution_users', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignIdFor(Institution::class);
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(InstitutionUserStatus::class);
            $table->unique(['institution_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_users');
    }
};
