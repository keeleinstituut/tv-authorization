<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ExcludeIfRelatedUserSoftDeletedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('user');
    }
}
