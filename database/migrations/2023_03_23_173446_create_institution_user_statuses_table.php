<?php

use App\Models\InstitutionUserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KEYS = [
        'CREATED',
        'ACTIVATED',
        'DEACTIVATED',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('institution_user_statuses', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->text('key')->unique();
            $table->comment('Lookup table storing the set of valid values for institution_user.status');
        });

        collect(self::KEYS)
            ->map(fn ($key) => new InstitutionUserStatus(['key' => $key]))
            ->each(fn ($model) => $model->save());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_user_statuses');
    }
};
