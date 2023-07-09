<?php

namespace App\Observers;

use App\Events\Publishers\InstitutionUserEventsPublisher;
use App\Exceptions\DeniedRootRoleModifyException;
use App\Models\PrivilegeRole;
use App\Models\Role;

readonly class PrivilegeRoleObserver
{
    public function __construct(private InstitutionUserEventsPublisher $publisher)
    {
    }

    /**
     * Handle the PrivilegeRole "created" event.
     */
    public function created(PrivilegeRole $privilegeRole): void
    {
        $this->publishAffectedInstitutionUsers($privilegeRole);
    }

    /**
     * Handle the PrivilegeRole "updating" event.
     */
    public function updating(PrivilegeRole $privilegeRole): void
    {
        $dirtyRoleId = $privilegeRole->isDirty('role_id');
        $dirtyPrivilegeId = $privilegeRole->isDirty('privilege_id');

        if ($dirtyPrivilegeId || $dirtyRoleId) {
            if ($dirtyRoleId) {
                $roleToCheck = Role::findOrFail($privilegeRole->getOriginal('role_id'));
            } else {
                $roleToCheck = $privilegeRole->role;
            }

            if ($roleToCheck->is_root) {
                throw new DeniedRootRoleModifyException();
            }
        }
    }

    /**
     * Handle the PrivilegeRole "updated" event.
     */
    public function updated(PrivilegeRole $privilegeRole): void
    {
        $this->publishAffectedInstitutionUsers($privilegeRole);
    }

    /**
     * Handle the PrivilegeRole "deleting" event.
     *
     * @throws DeniedRootRoleModifyException
     */
    public function deleting(PrivilegeRole $privilegeRole): void
    {
        if ($privilegeRole->role->is_root) {
            throw new DeniedRootRoleModifyException();
        }
    }

    /**
     * Handle the PrivilegeRole "deleted" event.
     */
    public function deleted(PrivilegeRole $privilegeRole): void
    {
        $this->publishAffectedInstitutionUsers($privilegeRole);
    }

    /**
     * Handle the PrivilegeRole "restored" event.
     */
    public function restored(PrivilegeRole $privilegeRole): void
    {
    }

    /**
     * Handle the PrivilegeRole "force deleted" event.
     */
    public function forceDeleted(PrivilegeRole $privilegeRole): void
    {
    }

    private function publishAffectedInstitutionUsers(PrivilegeRole $privilegeRole): void
    {
        $privilegeRole->role->institutionUserRoles()->pluck('institution_user_id')
            ->each(fn (string $institutionUserId) => $this->publisher->publishSyncEvent($institutionUserId));
    }
}
