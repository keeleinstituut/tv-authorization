<?php

namespace App\Events\Publishers;

use SyncTools\AmqpPublisher;

readonly class InstitutionEventsPublisher
{
    public function __construct(private AmqpPublisher $publisher)
    {
    }

    public function publishSavedEvent(string $institutionUserId): void
    {
        $this->publishEvent($institutionUserId, 'institution.saved');
    }

    public function publishDeletedEvent(string $institutionUserId): void
    {
        $this->publishEvent($institutionUserId, 'institution.deleted');
    }

    private function publishEvent(string $institutionId, string $routingKey): void
    {
        $this->publisher->publish(
            ['id' => $institutionId],
            'institution',
            $routingKey
        );
    }
}
