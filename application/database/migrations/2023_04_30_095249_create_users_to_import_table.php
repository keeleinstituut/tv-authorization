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
        Schema::create('users_to_import', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institution_user_id')->constrained();
            $table->text('name')->nullable();
            $table->text('role')->nullable();
            $table->text('personal_identification_code')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->text('department')->nullable();
            $table->boolean('is_vendor')->default(false);
            $table->integer('file_row_idx')->unsigned();
            $table->integer('errors_count')->unsigned()->default(0);
            $table->json('errors');
            $table->timestampsTz();

            $table->index(['file_row_idx', 'errors_count'], 'users_to_import-file_row_idx-errors_count-idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_to_import');
    }
};
