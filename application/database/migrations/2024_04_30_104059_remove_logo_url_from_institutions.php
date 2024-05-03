<?php

use App\Models\Institution;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $log = new ConsoleOutput();
        $log->writeln("\n");

        DB::transaction(function () use ($log) {
            Institution::all()->each(function ($i) use ($log) {
                $log->writeln("ID: $i->id");
                $log->writeln("URL: $i->logo_url");

                if ($i->logo_url == null) {
                    $log->writeln("Institution $i->id logo_url is empty/null. Skipping...");
                    return;
                }

                try {
                    $i->addMediaFromUrl($i->logo_url)
                        ->toMediaCollection(Institution::LOGO_MEDIA_COLLECTION);
                } catch (\Spatie\MediaLibrary\MediaCollections\Exceptions\UnreachableUrl $e) {
                    $log->writeln("Institution $i->id logo_url is not reachable ($i->logo_url). Skipping...");
                }
            });
        });

        $log->writeln("\n");


        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('logo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('logo_url')->nullable();
        });
    }
};
