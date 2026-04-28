<?php

namespace App\Policies;

use App\Enums\PrivilegeKey;
use App\Models\AuthUser;
use App\Models\Department;
use App\Policies\Scope\DepartmentScope;
use BadMethodCallException;

class DepartmentPolicy
{
    public function view(AuthUser $user, Department $department): bool
    {
        return ! $user->isTranslationAgency()
            && $this->isInSameInstitutionAsCurrentUser($user, $department);
    }

    public function create(AuthUser $user): bool
    {
        return ! $user->isTranslationAgency()
            && $user->hasPrivilege(PrivilegeKey::AddDepartment);
    }

    public function update(AuthUser $user, Department $department): bool
    {
        return ! $user->isTranslationAgency()
            && $this->isInSameInstitutionAsCurrentUser($user, $department)
            && $user->hasPrivilege(PrivilegeKey::EditDepartment);
    }

    public function delete(AuthUser $user, Department $department): bool
    {
        return ! $user->isTranslationAgency()
            && $this->isInSameInstitutionAsCurrentUser($user, $department)
            && $user->hasPrivilege(PrivilegeKey::DeleteDepartment);
    }

    public function restore(): bool
    {
        throw new BadMethodCallException();
    }

    /** @noinspection PhpUnused */
    public function forceDelete(): bool
    {
        throw new BadMethodCallException();
    }

    public function bulkUpdate(AuthUser $user): bool
    {
        return ! $user->isTranslationAgency()
            && $user->hasPrivilege(PrivilegeKey::AddDepartment)
            && $user->hasPrivilege(PrivilegeKey::EditDepartment)
            && $user->hasPrivilege(PrivilegeKey::DeleteDepartment);
    }

    /** @noinspection PhpUnused */
    public function isInSameInstitutionAsCurrentUser(AuthUser $user, Department $department): bool
    {
        return filled($user->institutionId)
            && $user->institutionId === $department->institution_id;
    }

    public static function scope(): DepartmentScope
    {
        return new Scope\DepartmentScope();
    }
}

// Scope resides in the same file with Policy to enforce scope creation with policy creation.

namespace App\Policies\Scope;

use App\Models\AuthUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as IScope;
use Illuminate\Support\Facades\Auth;

class DepartmentScope implements IScope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var AuthUser|null $user */
        $user = Auth::user();
        $authenticatedInstitutionId = $user?->institutionId;
        abort_if(empty($authenticatedInstitutionId), 401);
        abort_if($user->isTranslationAgency(), 403);
        $builder->where('institution_id', $authenticatedInstitutionId);
    }
}
