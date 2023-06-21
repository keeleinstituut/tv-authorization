<?php

namespace App\Events\Publishers;

use SyncTools\AmqpPublisher;

readonly class InstitutionUserEventsPublisher
{
    public function __construct(private AmqpPublisher $publisher)
    {
    }

    public function publishSyncEvent(string $institutionUserId): void
    {
        $this->publishEvent($institutionUserId, 'institution-user.saved');
    }

    public function publishDeleteEvent(string $institutionUserId): void
    {
        $this->publishEvent($institutionUserId, 'institution-user.deleted');
    }

    private function publishEvent(string $institutionUserId, string $routingKey): void
    {
        $this->publisher->publish(
            ['id' => $institutionUserId],
            'institution-user',
            $routingKey
        );
    }
}
