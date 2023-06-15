<?php

namespace App\Policies;

use App\Enums\PrivilegeKey;
use App\Models\Department;
use App\Policies\Scope\DepartmentScope;
use BadMethodCallException;
use Illuminate\Support\Facades\Auth;
use KeycloakAuthGuard\Models\JwtPayloadUser;

class DepartmentPolicy
{
    /** @noinspection PhpUnusedParameterInspection */
    public function view(JwtPayloadUser $ignored, Department $department): bool
    {
        return $this->isInSameInstitutionAsCurrentUser($department);
    }

    public function create(): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::AddDepartment->value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function update(JwtPayloadUser $ignored, Department $department): bool
    {
        return $this->isInSameInstitutionAsCurrentUser($department)
            && Auth::hasPrivilege(PrivilegeKey::EditDepartment->value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function delete(JwtPayloadUser $ignored, Department $department): bool
    {
        return $this->isInSameInstitutionAsCurrentUser($department)
            && Auth::hasPrivilege(PrivilegeKey::DeleteDepartment->value);
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

    /** @noinspection PhpUnused */
    public function isInSameInstitutionAsCurrentUser(Department $department): bool
    {
        return filled($currentInstitutionId = Auth::user()?->institutionId)
            && $currentInstitutionId === $department->institution_id;
    }

    public static function scope(): DepartmentScope
    {
        return new Scope\DepartmentScope();
    }
}

// Scope resides in the same file with Policy to enforce scope creation with policy creation.

namespace App\Policies\Scope;

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
        $authenticatedInstitutionId = Auth::user()->institutionId;
        abort_if(empty($authenticatedInstitutionId), 401);
        $builder->where('institution_id', $authenticatedInstitutionId);
    }
}
