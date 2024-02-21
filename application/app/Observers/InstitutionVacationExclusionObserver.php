<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Models\InstitutionVacationExclusion;

readonly class InstitutionVacationExclusionObserver
{
    public function __construct(private InstitutionUserEventsPublisher $syncPublisher)
    {
    }

    /**
     * Handle the InstitutionVacationExclusion "saved" event.
     */
    public function saved(InstitutionVacationExclusion $institutionVacationExclusion): void
    {
        $this->syncPublisher->publishSyncEvent($institutionVacationExclusion->institution_user_id);
    }

    /**
     * Handle the InstitutionVacationExclusion "deleted" event.
     */
    public function deleted(InstitutionVacationExclusion $institutionVacationExclusion): void
    {
        $this->syncPublisher->publishSyncEvent($institutionVacationExclusion->institution_user_id);
    }

    /**
     * Handle the InstitutionVacationExclusion "restored" event.
     */
    public function restored(InstitutionVacationExclusion $institutionVacationExclusion): void
    {
        //
    }

    /**
     * Handle the InstitutionVacationExclusion "force deleted" event.
     */
    public function forceDeleted(InstitutionVacationExclusion $institutionVacationExclusion): void
    {
        //
    }
}
