<?php

namespace App\Observers;

use App\Models\Institution;
use SyncTools\AmqpPublisher;

class InstitutionObserver
{
    /**
     * Handle events after all transactions are committed.
     */
    public bool $afterCommit = true;

    public function __construct(private readonly AmqpPublisher $publisher)
    {
    }

    /**
     * Handle the Institution "saved" event.
     */
    public function saved(Institution $institution): void
    {
        $this->publishEvent($institution, 'institution.saved');
    }

    public function deleted(Institution $institution): void
    {
        $this->publishEvent($institution, 'institution.saved');
    }

    /**
     * Handle the Institution "deleted" event.
     */
    public function forceDeleted(Institution $institution): void
    {
        $this->publishEvent($institution, 'institution.deleted');
    }

    private function publishEvent(Institution $institution, string $routingKey = ''): void
    {
        $this->publisher->publish(
            ['id' => $institution->id],
            'institution',
            $routingKey
        );
    }
}
