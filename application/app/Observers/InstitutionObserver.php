<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionEventsPublisher;
use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Models\Institution;

class InstitutionObserver
{
    /**
     * Handle events after all transactions are committed.
     */
    public bool $afterCommit = true;

    public function __construct(
        private readonly InstitutionEventsPublisher $institutionPublisher,
        private readonly InstitutionUserEventsPublisher $institutionUserPublisher
    ) {
    }

    /**
     * Handle the Institution "saved" event.
     */
    public function saved(Institution $institution): void
    {
        $this->institutionPublisher->publishSavedEvent($institution->id);
        $this->publishAffectedInstitutionUsers($institution);
    }

    public function deleted(Institution $institution): void
    {
        $this->institutionPublisher->publishSavedEvent($institution->id);
        $this->publishAffectedInstitutionUsers($institution);
    }

    /**
     * Handle the Institution "deleted" event.
     */
    public function forceDeleted(Institution $institution): void
    {
        $this->institutionPublisher->publishDeletedEvent($institution->id);
        $this->publishAffectedInstitutionUsers($institution);
    }

    private function publishAffectedInstitutionUsers(Institution $institution): void
    {
        $institution->institutionUsers()->pluck('id')
            ->each(fn (string $institutionUserId) => $this->institutionUserPublisher->publishSyncEvent($institutionUserId));
    }
}
