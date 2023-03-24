<?php

use App\Models\InstitutionUser;
use App\Models\Role;
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
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignIdFor(InstitutionUser::class);
            $table->foreignIdFor(Role::class);
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
