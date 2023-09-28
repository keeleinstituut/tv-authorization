<?php

namespace App\Policies;

use App\Enums\PrivilegeKey;
use App\Models\InstitutionUser;
use App\Policies\Scope\InstitutionUserScope;
use BadMethodCallException;
use Illuminate\Support\Facades\Auth;
use KeycloakAuthGuard\Models\JwtPayloadUser;

class InstitutionUserPolicy
{
    /** @noinspection PhpUnused PhpUnusedParameterInspection */
    public function viewAny(JwtPayloadUser $jwtPayloadUser): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::ViewUser->value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function view(JwtPayloadUser $jwtPayloadUser, InstitutionUser $institutionUser): bool
    {
        return $this->isCurrentUser($institutionUser)
            || Auth::hasPrivilege(PrivilegeKey::ViewUser->value)
            && $this->isInSameInstitutionAsCurrentUser($institutionUser);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function create(JwtPayloadUser $jwtPayloadUser): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::AddUser->value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function update(JwtPayloadUser $jwtPayloadUser, InstitutionUser $institutionUser): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::EditUser->value)
            && $this->isInSameInstitutionAsCurrentUser($institutionUser);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function delete(JwtPayloadUser $jwtPayloadUser, InstitutionUser $institutionUser): bool
    {
        throw new BadMethodCallException();
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function restore(JwtPayloadUser $jwtPayloadUser, InstitutionUser $institutionUser): bool
    {
        throw new BadMethodCallException();
    }

    public function forceDelete(): bool
    {
        throw new BadMethodCallException();
    }

    public function export(): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::ExportUser->value);
    }

    public function import()
    {
        return Auth::hasPrivilege(PrivilegeKey::AddUser->value);
    }

    /** @noinspection PhpUnused PhpUnusedParameterInspection */
    public function deactivate(JwtPayloadUser $jwtPayloadUser, InstitutionUser $institutionUser): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::DeactivateUser->value)
            && $this->isInSameInstitutionAsCurrentUser($institutionUser);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function activate(JwtPayloadUser $jwtPayloadUser, InstitutionUser $institutionUser): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::ActivateUser->value)
            && $this->isInSameInstitutionAsCurrentUser($institutionUser);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function archive(JwtPayloadUser $jwtPayloadUser, InstitutionUser $institutionUser): bool
    {
        return Auth::hasPrivilege(PrivilegeKey::ArchiveUser->value)
            && $this->isInSameInstitutionAsCurrentUser($institutionUser);
    }

    public function isCurrentUser(InstitutionUser $institutionUser): bool
    {
        return filled($currentUserId = Auth::user()?->id)
            && $currentUserId === $institutionUser->id;
    }

    public function isInSameInstitutionAsCurrentUser(InstitutionUser $institutionUser): bool
    {
        return filled($currentInstitutionId = Auth::user()?->institutionId)
            && $currentInstitutionId === $institutionUser->institution_id;
    }

    // Should serve as a query enhancement to Eloquent queries
    // to filter out objects that the user does not have permissions to.
    //
    // Example usage in query:
    // Role::getModel()->withGlobalScope('policy', RolePolicy::scope())->get();
    //
    // The 'policy' string in the example is not strict and is used internally to identify
    // the scope applied in Eloquent querybuilder. It can be something else as well,
    // but it should correspond with the intentions of the scope. Using 'policy' provides
    // general understanding throughout the whole project that the applied scope is related to policy.
    // The withGlobalScope method does not apply the scope globally, it applies to only the querybuilder
    // of current query. The method name could be different, but in the sake of reusability
    // we can use this method that's provided by Laravel and used internally.
    //
    public static function scope(): InstitutionUserScope
    {
        return new Scope\InstitutionUserScope();
    }
}

// Scope resides in the same file with Policy to enforce scope creation with policy creation.

namespace App\Policies\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as IScope;
use Illuminate\Support\Facades\Auth;

class InstitutionUserScope implements IScope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (empty($currentUserInstitutionId = Auth::user()?->institutionId)) {
            abort(401);
        }

        $builder->where('institution_id', $currentUserInstitutionId);
    }
}
