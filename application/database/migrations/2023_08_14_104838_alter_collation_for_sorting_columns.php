<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE institutions ALTER COLUMN name TYPE TEXT COLLATE "et-EE-x-icu"');
        DB::statement('ALTER TABLE institutions ALTER COLUMN short_name TYPE VARCHAR(3) COLLATE "et-EE-x-icu"');
        DB::statement('ALTER TABLE departments ALTER COLUMN name TYPE TEXT COLLATE "et-EE-x-icu"');
        DB::statement('ALTER TABLE roles ALTER COLUMN name TYPE TEXT COLLATE "et-EE-x-icu"');
        DB::statement('ALTER TABLE users ALTER COLUMN forename TYPE TEXT COLLATE "et-EE-x-icu"');
        DB::statement('ALTER TABLE users ALTER COLUMN surname TYPE TEXT COLLATE "et-EE-x-icu"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE institutions ALTER COLUMN name TYPE text COLLATE "default"');
        DB::statement('ALTER TABLE institutions ALTER COLUMN short_name TYPE VARCHAR(3) COLLATE "default"');
        DB::statement('ALTER TABLE departments ALTER COLUMN name TYPE text COLLATE "default"');
        DB::statement('ALTER TABLE roles ALTER COLUMN name TYPE text COLLATE "default"');
        DB::statement('ALTER TABLE users ALTER COLUMN forename TYPE TEXT COLLATE "default"');
        DB::statement('ALTER TABLE users ALTER COLUMN surname TYPE TEXT COLLATE "default"');
    }
};
