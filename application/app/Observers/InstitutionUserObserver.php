<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Exceptions\OnlyUserUnderRootRoleException;
use App\Models\InstitutionUser;

class InstitutionUserObserver
{
    /**
     * Handle events after all transactions are committed.
     */
    public bool $afterCommit = true;

    public function __construct(private readonly InstitutionUserEventsPublisher $publisher)
    {
    }

    /**
     * Handle the InstitutionUser "updating" event.
     *
     * @throws OnlyUserUnderRootRoleException
     */
    public function updating(InstitutionUser $institutionUser): void
    {
        if ($institutionUser->isDirty(['archived_at', 'deactivation_date'])) {
            if ($institutionUser->isOnlyUserWithRootRole()) {
                throw new OnlyUserUnderRootRoleException();
            }
        }
    }

    /**
     * Handle the InstitutionUser "saved" event.
     */
    public function saved(InstitutionUser $institutionUser): void
    {
        $this->publisher->publishChangedEvent($institutionUser->id);
    }

    /**
     * Handle the InstitutionUser "deleting" event.
     *
     * @throws OnlyUserUnderRootRoleException
     */
    public function deleting(InstitutionUser $institutionUser): void
    {
        if ($institutionUser->isOnlyUserWithRootRole()) {
            throw new OnlyUserUnderRootRoleException();
        }
    }

    /**
     * Handle the InstitutionUser "deleted" event.
     */
    public function deleted(InstitutionUser $institutionUser): void
    {
        $this->publisher->publishChangedEvent($institutionUser->id);
    }

    /**
     * Handle the InstitutionUser "restored" event.
     */
    public function restored(InstitutionUser $institutionUser): void
    {
        $this->publisher->publishChangedEvent($institutionUser->id);
    }

    /**
     * Handle the InstitutionUser "force deleted" event.
     */
    public function forceDeleted(InstitutionUser $institutionUser): void
    {
        $this->publisher->publishDeletedEvent($institutionUser->id);
    }
}
