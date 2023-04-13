<?php

use App\Models\Privilege;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        collect(self::KEYS)
            ->map(fn ($key) => new Privilege(['key' => $key]))
            ->each(fn ($model) => $model->save());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('privileges')
            ->whereIn('key', self::KEYS)
            ->delete();
    }
};
