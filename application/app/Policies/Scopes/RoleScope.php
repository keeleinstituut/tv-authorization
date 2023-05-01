<?php

namespace App\Policies\Scopes;

use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use KeycloakAuthGuard\Models\JwtPayloadUser;

class RoleScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var JwtPayloadUser $user */
        $user = Auth::user();
        $builder->where('institution_id', $user->institutionId);
    }
}
