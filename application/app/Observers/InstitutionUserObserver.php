<?php

namespace App\Observers;

use Amqp\Publisher;
use App\Models\InstitutionUser;

class InstitutionUserObserver
{
    public function __construct(private readonly Publisher $publisher)
    {
    }

    /**
     * Handle the InstitutionUser "saved" event.
     */
    public function saved(InstitutionUser $institutionUser): void
    {
        $this->publishEvent($institutionUser, 'institution-user.saved');
    }

    /**
     * Handle the InstitutionUser "deleted" event.
     */
    public function deleted(InstitutionUser $institutionUser): void
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
