<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Exceptions\OnlyUserUnderRootRoleException;
use App\Models\InstitutionUserRole;
use App\Models\Role;

class InstitutionUserRoleObserver
{
    public function __construct(private readonly InstitutionUserEventsPublisher $publisher)
    {
    }

    /**
     * Handle the InstitutionUserRole "created" event.
     */
    public function created(InstitutionUserRole $institutionUserRole): void
    {
        $this->publishAffectedInstitutionUser($institutionUserRole);
    }

    /**
     * Handle the InstitutionUserRole "updating" event.
     *
     * @throws OnlyUserUnderRootRoleException
     */
    public function updating(InstitutionUserRole $institutionUserRole): void
    {
        if ($institutionUserRole->isDirty('role_id')) {
            $originalRole = Role::findOrFail($institutionUserRole->getOriginal('role_id'));
            if ($originalRole->is_root && $institutionUserRole->institutionUser->isOnlyUserWithRootRole()) {
                throw new OnlyUserUnderRootRoleException();
            }
        }
    }

    /**
     * Handle the InstitutionUserRole "updated" event.
     */
    public function updated(InstitutionUserRole $institutionUserRole): void
    {
        $this->publishAffectedInstitutionUser($institutionUserRole);
    }

    /**
     * Handle the InstitutionUserRole "deleting" event.
     *
     * @throws OnlyUserUnderRootRoleException
     */
    public function deleting(InstitutionUserRole $institutionUserRole): void
    {
        if ($institutionUserRole->role->is_root && $institutionUserRole->institutionUser->isOnlyUserWithRootRole()) {
            throw new OnlyUserUnderRootRoleException();
        }
    }

    /**
     * Handle the InstitutionUserRole "deleted" event.
     */
    public function deleted(InstitutionUserRole $institutionUserRole): void
    {
        $this->publishAffectedInstitutionUser($institutionUserRole);
    }

    /**
     * Handle the InstitutionUserRole "restored" event.
     */
    public function restored(InstitutionUserRole $institutionUserRole): void
    {
        $this->publishAffectedInstitutionUser($institutionUserRole);
    }

    /**
     * Handle the InstitutionUserRole "force deleted" event.
     */
    public function forceDeleted(InstitutionUserRole $institutionUserRole): void
    {
        $this->publishAffectedInstitutionUser($institutionUserRole);
    }

    private function publishAffectedInstitutionUser(InstitutionUserRole $institutionUserRole): void
    {
        $this->publisher->publishSyncEvent($institutionUserRole->institution_user_id);
    }
}
