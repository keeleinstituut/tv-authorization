<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Models\User;

class UserObserver
{
    public function __construct(private readonly InstitutionUserEventsPublisher $institutionUserPublisher)
    {
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $this->publishAffectedInstitutionUsers($user);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->publishAffectedInstitutionUsers($user);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $this->publishAffectedInstitutionUsers($user);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        $this->publishAffectedInstitutionUsers($user);
    }

    private function publishAffectedInstitutionUsers(User $user): void
    {
        $user->institutionUsers()->pluck('id')
            ->each(fn(string $institutionUserId) => $this->institutionUserPublisher->publishSyncEvent(
                $institutionUserId
            ));
    }
}
