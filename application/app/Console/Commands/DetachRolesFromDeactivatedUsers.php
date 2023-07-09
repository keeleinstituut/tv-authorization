<?php

namespace App\Console\Commands;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Models\InstitutionUserRole;
use App\Util\DateUtil;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

class DetachRolesFromDeactivatedUsers extends Command implements Isolatable
{
    public final const COMMAND_NAME = 'app:detach-roles-from-deactivated-users';

    protected $signature = self::COMMAND_NAME;

    protected $description = 'Detach roles from institution users who have been deactivated.';

    /**
     * @throws Throwable
     */
    public function handle(InstitutionUserEventsPublisher $publisher)
    {
        DB::transaction(function () use ($publisher) {
            InstitutionUserRole::query()
                ->whereIn(
                    'institution_user_id',
                    DB::table('institution_users')
                        ->select('id')
                        ->whereDate('deactivation_date', '<=', Date::now(DateUtil::ESTONIAN_TIMEZONE)->format('Y-m-d'))
                )->each(function (InstitutionUserRole $institutionUserRole) use ($publisher) {
                    $institutionUserRole->deleteQuietly();
                    $publisher->publishSyncEvent($institutionUserRole->institution_user_id);
                });

            // TODO: audit log
        });
    }
}
