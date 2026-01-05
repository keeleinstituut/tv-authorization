<?php

namespace Tests\Feature\Console;

use App\Console\Commands\DetachRolesFromDeactivatedUsers;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_detach_roles_from_deactivated_users_runs_after_midnight_estonian_time(): void
    {
        // GIVEN the current Estonian time is to 2000-01-01T02:59:59
        Date::setTestNow(Date::create(2000, 01, 01, 02, 59, 59, timezone: 'Europe/Tallinn'));

        // And (sanity check) the command is among due events
        $this->assertEmpty(
            $this->getDueEvents()->filter($this->isDetachCommandEvent(...))
        );

        // WHEN we travel to 2000-01-01T03:00:00 Estonian time
        $this->travelTo(Date::create(2000, 01, 01, 03, 00, 00, timezone: 'Europe/Tallinn'));

        // THEN the command should be among due events
        $this->assertNotEmpty(
            $this->getDueEvents()->filter($this->isDetachCommandEvent(...))
        );
    }

    /** @return Collection<Event> */
    public function getDueEvents(): Collection
    {
        return collect(app(Schedule::class)->dueEvents($this->app));
    }

    private function isDetachCommandEvent(Event $event): bool
    {
        return filled($event->command)
            && Str::contains(
                $event->command,
                DetachRolesFromDeactivatedUsers::COMMAND_NAME
            );
    }
}
