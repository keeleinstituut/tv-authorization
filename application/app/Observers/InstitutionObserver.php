<?php

namespace App\Observers;

use Amqp\Publisher;
use App\Models\Institution;

class InstitutionObserver
{
    /**
     * Handle events after all transactions are committed.
     */
    public bool $afterCommit = true;

    public function __construct(private readonly Publisher $publisher)
    {
    }


    /**
     * Handle the Institution "saved" event.
     */
    public function saved(Institution $institution): void
    {
        $this->publishEvent($institution, 'institution.saved');
    }

    /**
     * Handle the Institution "deleted" event.
     */
    public function deleted(Institution $institution): void
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
