<?php

namespace App\Observers;

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
     * Handle the InstitutionUser "saved" event.
     */
    public function saved(InstitutionUser $institutionUser): void
    {
        $this->publishEvent($institutionUser, 'institution-user.saved');
    }

    public function deleted(InstitutionUser $institutionUser): void
    {
        $this->publishEvent($institutionUser, 'institution-user.saved');
    }

    /**
     * Handle the InstitutionUser "deleted" event.
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
