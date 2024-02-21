<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Models\InstitutionUser;
use App\Models\InstitutionVacation;

readonly class InstitutionVacationObserver
{
    public function __construct(private InstitutionUserEventsPublisher $syncPublisher)
    {
    }

    /**
     * Handle the InstitutionUser "saved" event.
     */
    public function saved(InstitutionVacation $institutionVacation): void
    {
        $this->publishInstitutionUsersSyncEvents($institutionVacation);
    }

    /**
     * Handle the InstitutionVacation "deleted" event.
     */
    public function deleted(InstitutionVacation $institutionVacation): void
    {
        $this->publishInstitutionUsersSyncEvents($institutionVacation);
    }

    /**
     * Handle the InstitutionVacation "restored" event.
     */
    public function restored(InstitutionVacation $institutionVacation): void
    {
        //
    }

    /**
     * Handle the InstitutionVacation "force deleted" event.
     */
    public function forceDeleted(InstitutionVacation $institutionVacation): void
    {
        //
    }

    private function publishInstitutionUsersSyncEvents(InstitutionVacation $institutionVacation): void
    {
        InstitutionUser::query()->where('institution_id', $institutionVacation->institution_id)
            ->pluck('id')->each(fn (string $id) => $this->syncPublisher->publishSyncEvent($id));
    }
}
