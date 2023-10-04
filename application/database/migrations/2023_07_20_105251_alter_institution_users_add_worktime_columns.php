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
        Schema::table('institution_users', function (Blueprint $table) {
            $table->string('worktime_timezone')->nullable();

            collect(static::getWorktimeColumnsByDay())
                ->eachSpread(function ($start, $end) use ($table) {
                    $table->time($start)->nullable();
                    $table->time($end)->nullable();
                });
        });

        $ianaTimezoneNames = collect(DateTimeZone::listIdentifiers())->map(fn ($name) => "'$name'")->join(', ');

        DB::statement(<<<EOD
            ALTER TABLE institution_users
            ADD CONSTRAINT institution_users_worktime_timezone_check
            CHECK ( worktime_timezone IS NULL OR worktime_timezone IN ($ianaTimezoneNames) )
            EOD
        );

        collect(self::getWorktimeColumnsByDay())
            ->eachSpread(function ($start, $end) {
                $compositeConstaintName = 'institutions_'.Str::before($start, '_start').'_check';
                DB::statement(<<<EOD
                    ALTER TABLE institution_users
                    ADD CONSTRAINT $compositeConstaintName
                    CHECK (
                        $start IS NULL AND $end IS NULL
                            OR
                        worktime_timezone IS NOT NULL AND $start IS NOT NULL AND $end IS NOT NULL AND $start < $end
                    )
                    EOD
                );
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_users', function (Blueprint $table) {
            collect(static::getWorktimeColumnsByDay())
                ->flatten()
                ->each(fn ($column) => $table->dropColumn($column));

        });
    }

    /** @return array<array{string, string}> */
    private static function getWorktimeColumnsByDay(): array
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->map(fn (string $day) => [
                "{$day}_worktime_start",
                "{$day}_worktime_end",
            ])
            ->all();
    }
};
