<?php

use App\Models\Privilege;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KEYS = [
        'ADD_ROLE',
        'VIEW_ROLE',
        'EDIT_ROLE',
        'DELETE_ROLE',
        'ADD_USER',
        'EDIT_USER',
        'VIEW_USER',
        'EXPORT_USER',
        'ACTIVATE_USER',
        'DEACTIVATE_USER',
        'ARCHIVE_USER',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('privileges', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->text('key')->unique();
            $table->text('description')->nullable();
            $table->comment('A privilege authorizes the user to perform a set of actions');
        });

        collect(self::KEYS)
            ->map(fn ($key) => new Privilege(['key' => $key]))
            ->each(fn ($model) => $model->save());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privileges');
    }
};
