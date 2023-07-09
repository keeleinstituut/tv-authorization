<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Models\Department;

readonly class DepartmentObserver
{
    public function __construct(private InstitutionUserEventsPublisher $institutionUserPublisher)
    {
    }

    /**
     * Handle the Department "created" event.
     */
    public function created(Department $department): void
    {
        //
    }

    /**
     * Handle the Department "updated" event.
     */
    public function updated(Department $department): void
    {
        $this->publishAffectedInstitutionUsers($department);
    }

    /**
     * Handle the Department "deleted" event.
     */
    public function deleted(Department $department): void
    {
        $this->publishAffectedInstitutionUsers($department);
    }

    /**
     * Handle the Department "restored" event.
     */
    public function restored(Department $department): void
    {
    }

    /**
     * Handle the Department "force deleted" event.
     */
    public function forceDeleted(Department $department): void
    {
    }

    private function publishAffectedInstitutionUsers(Department $department): void
    {
        $department->institutionUsers()->pluck('id')
            ->each(fn (string $institutionUserId) => $this->institutionUserPublisher->publishSyncEvent(
                $institutionUserId
            ));
    }
}
