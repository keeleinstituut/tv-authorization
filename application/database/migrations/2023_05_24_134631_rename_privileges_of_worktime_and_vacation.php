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
        Schema::table('privileges', function (Blueprint $table) {
            DB::table($table->getTable())
                ->where(['key' => 'SET_USER_WORKTIME'])
                ->update(['key' => 'EDIT_USER_WORKTIME']);

            DB::table($table->getTable())
                ->where(['key' => 'SET_USER_VACATION'])
                ->update(['key' => 'EDIT_USER_VACATION']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('privileges', function (Blueprint $table) {
            DB::table($table->getTable())
                ->where(['key' => 'EDIT_USER_WORKTIME'])
                ->update(['key' => 'SET_USER_WORKTIME']);

            DB::table($table->getTable())
                ->where(['key' => 'EDIT_USER_VACATION'])
                ->update(['key' => 'SET_USER_VACATION']);
        });
    }
};
