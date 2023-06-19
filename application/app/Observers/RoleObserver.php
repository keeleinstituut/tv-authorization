<?php

namespace App\Observers;

use App\Exceptions\DeniedRootRoleModifyException;
use App\Models\Role;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "updating" event.
     * @throws DeniedRootRoleModifyException
     */
    public function updating(Role $role): void
    {
        if ($role->isDirty('is_root')) {
            if ($role->getOriginal('is_root')) {
                throw new DeniedRootRoleModifyException();
            }
        }
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "deleting" event.
     *
     * @throws DeniedRootRoleModifyException
     */
    public function deleting(Role $role): void
    {
        if ($role->is_root) {
            throw new DeniedRootRoleModifyException();
        }
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $role->privilegeRoles()->delete();
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "force deleting" event.
     */
    public function forceDeleting(Role $role): void
    {
        $role->privilegeRoles()->forceDelete();
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        //
    }
}
