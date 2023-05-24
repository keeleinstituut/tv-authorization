<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Date;

class ExcludeDeactivatedInstitutionUsersScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where(
            fn ($groupedClause) => $groupedClause
                ->whereNull('deactivation_date')
                ->orWhereDate('deactivation_date', '>', Date::now('Europe/Tallinn'))
        );
    }
}
