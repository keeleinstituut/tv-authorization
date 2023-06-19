<?php

namespace App\Observers;

use App\Exceptions\OnlyUserUnderRootRoleException;
use App\Models\InstitutionUser;
use SyncTools\AmqpPublisher;

class InstitutionUserObserver
{
    /**
     * Handle events after all transactions are committed.
     */
    public bool $afterCommit = true;

    public function __construct(private readonly AmqpPublisher $publisher)
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
        $this->publishEvent($institutionUser, 'institution-user.saved');
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
        $this->publishEvent($institutionUser, 'institution-user.saved');
    }

    /**
     * Handle the InstitutionUser "restored" event.
     */
    public function restored(InstitutionUser $institutionUser): void
    {
        //
    }

    /**
     * Handle the InstitutionUser "force deleted" event.
     */
    public function forceDeleted(InstitutionUser $institutionUser): void
    {
        $this->publishEvent($institutionUser, 'institution-user.deleted');
    }

    private function publishEvent(InstitutionUser $institutionUser, string $routingKey = ''): void
    {
        $this->publisher->publish(
            ['id' => $institutionUser->id],
            'institution-user',
            $routingKey
        );
    }
}
