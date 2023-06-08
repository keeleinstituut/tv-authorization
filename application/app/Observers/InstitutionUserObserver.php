<?php

namespace App\Observers;

use App\Exceptions\OnlyUserUnderRootRoleException;
use App\Models\InstitutionUser;

class InstitutionUserObserver
{
    /**
     * Handle the InstitutionUser "created" event.
     */
    public function created(InstitutionUser $institutionUser): void
    {
        //
    }

    /**
     * Handle the InstitutionUser "updating" event.
     * @throws OnlyUserUnderRootRoleException
     */
    public function updating(InstitutionUser $institutionUser): void
    {
        if ($institutionUser->isDirty(['archived_at', 'deactivation_date'])) {
            if ($institutionUser->hasRootRole() && $institutionUser->isOnlyUserWithRootRole()) {
                throw new OnlyUserUnderRootRoleException();
            }
        }
    }

    /**
     * Handle the InstitutionUser "updated" event.
     */
    public function updated(InstitutionUser $institutionUser): void
    {
        //
    }

    /**
     * Handle the InstitutionUser "deleting" event.
     * @throws OnlyUserUnderRootRoleException
     */
    public function deleting(InstitutionUser $institutionUser): void
    {
        if ($institutionUser->hasRootRole() && $institutionUser->isOnlyUserWithRootRole()) {
            throw new OnlyUserUnderRootRoleException();
        }
    }

    /**
     * Handle the InstitutionUser "deleted" event.
     */
    public function deleted(InstitutionUser $institutionUser): void
    {
        //
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
        //
    }
}
