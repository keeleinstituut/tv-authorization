<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('privileges')->where('key', 'RECEIVE_AND_MANAGE_PROJECT')
            ->update(['key' => 'RECEIVE_PROJECT']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('privileges')->where('key', 'RECEIVE_PROJECT')
            ->update(['key' => 'RECEIVE_AND_MANAGE_PROJECT']);
    }
};
