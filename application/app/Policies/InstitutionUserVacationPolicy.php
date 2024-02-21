<?php

namespace App\Policies;

use App\Enums\PrivilegeKey;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserVacation;
use Illuminate\Support\Facades\Auth;
use KeycloakAuthGuard\Models\JwtPayloadUser;

class InstitutionUserVacationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(JwtPayloadUser $user, InstitutionUser $institutionUser): bool
    {
        return $this->isCurrentUser($institutionUser->id) || (
            Auth::hasPrivilege(PrivilegeKey::EditUserVacation->value) &&
            $user->institutionId === $institutionUser->institution_id
        );
    }

    /**
     * Determine whether the user can view the model.
     */
    public function sync(JwtPayloadUser $user, InstitutionUser $institutionUser): bool
    {
        return $this->isCurrentUser($institutionUser->id) || (
            Auth::hasPrivilege(PrivilegeKey::EditUserVacation->value) &&
            $user->institutionId === $institutionUser->institution_id
        );
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(JwtPayloadUser $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(JwtPayloadUser $user, InstitutionUserVacation $institutionUserVacation): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(JwtPayloadUser $user, InstitutionUserVacation $institutionUserVacation): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(JwtPayloadUser $user, InstitutionUserVacation $institutionUserVacation): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(JwtPayloadUser $user, InstitutionUserVacation $institutionUserVacation): bool
    {
        return true;
    }

    public function isCurrentUser(string $institutionUserId): bool
    {
        return filled($currentUserId = Auth::user()?->institutionUserId)
            && $currentUserId === $institutionUserId;
    }

    // Should serve as an query enhancement to Eloquent queries
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
    public static function scope(): Scope\InstitutionUserVacationScope
    {
        return new Scope\InstitutionUserVacationScope();
    }
}

// Scope resides in the same file with Policy to enforce scope creation with policy creation.

namespace App\Policies\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as IScope;
use Illuminate\Support\Facades\Auth;

class InstitutionUserVacationScope implements IScope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('institutionUser', function (Builder $institutionUserQuery) {
            return $institutionUserQuery->where('institution_id', Auth::user()->institutionId);
        });
    }
}
