<?php

namespace App\Console\Commands;

use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Scopes\ExcludeArchivedInstitutionUsersScope;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Util\DateUtil;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

class DetachRolesFromDeactivatedUsers extends Command implements Isolatable
{
    public final const COMMAND_NAME = 'app:detach-roles-from-deactivated-users';

    protected $signature = self::COMMAND_NAME;

    protected $description = 'Detach roles from institution users who have been deactivated.';

    /** @throws Throwable */
    public function handle(): void
    {
        DB::transaction(function () {
            InstitutionUserRole::query()
                ->with(['role'])
                ->whereIn(
                    'institution_user_id',
                    DB::table('institution_users')
                        ->select('id')
                        ->whereDate('deactivation_date', '<=', Date::now(DateUtil::ESTONIAN_TIMEZONE)->format('Y-m-d'))
                )->each(function (InstitutionUserRole $institutionUserRole) {
                    if ($institutionUserRole->role?->is_root) {
                        /** @var InstitutionUser $institutionUser */
                        $institutionUser = $institutionUserRole->institutionUser()
                            ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
                            ->withoutGlobalScope(ExcludeArchivedInstitutionUsersScope::class)
                            ->first();

                        if (filled($institutionUser) && ! $this->institutionHasAnotherActiveUserWithRootRole($institutionUser)) {
                            $institutionUser->deactivation_date = null;
                            $institutionUser->archived_at = null;
                            $institutionUser->save();

                            return true;
                        }
                    }

                    try {
                        $institutionUserRole->delete();
                    } catch (Exception) {
                        // TODO: error log
                        // Continue execution since we don’t want the entire update to fail
                    }
                });

            // TODO: audit log
        });
    }

    private function institutionHasAnotherActiveUserWithRootRole(InstitutionUser $institutionUser): bool
    {
        return InstitutionUserRole::query()
            ->whereRelation('role', function (Builder $query) use ($institutionUser) {
                $query->where('is_root', true)
                    ->where('institution_id', $institutionUser->institution_id);
            })->whereRelation('institutionUser', function (Builder $query) use ($institutionUser) {
                $query->where('institution_id', $institutionUser->institution_id)
                    ->whereNot('id', $institutionUser->id);
            })->exists();
    }
}
