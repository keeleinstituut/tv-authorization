<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserVacation;

readonly class InstitutionUserVacationObserver
{
    public function __construct(private InstitutionUserEventsPublisher $syncPublisher)
    {
    }

    /**
     * Handle the InstitutionUser "saved" event.
     */
    public function saved(InstitutionUserVacation $institutionUserVacation): void
    {
        $this->syncPublisher->publishSyncEvent($institutionUserVacation->institution_user_id);
    }

    /**
     * Handle the InstitutionUserVacation "deleted" event.
     */
    public function deleted(InstitutionUserVacation $institutionUserVacation): void
    {
        $this->syncPublisher->publishSyncEvent($institutionUserVacation->institution_user_id);
    }

    /**
     * Handle the InstitutionUserVacation "restored" event.
     */
    public function restored(InstitutionUserVacation $institutionUserVacation): void
    {
        //
    }

    /**
     * Handle the InstitutionUserVacation "force deleted" event.
     */
    public function forceDeleted(InstitutionUserVacation $institutionUserVacation): void
    {
        //
    }
}
