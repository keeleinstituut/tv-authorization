<?php

namespace App\Http\Requests\Traits;

use App\Models\InstitutionUser;
use App\Models\Scopes\ExcludeArchivedInstitutionUsersScope;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Policies\InstitutionUserPolicy;
use Illuminate\Database\Eloquent\Builder;

trait FindsInstitutionUsersWithAnyStatus
{
    private function findInstitutionUserWithAnyStatus(?string $id): InstitutionUser
    {
        abort_if(empty($id), 404);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getInstitutionUserWithAnyStatusBaseQuery()->findOrFail($id);
    }

    private function getInstitutionUserWithAnyStatusBaseQuery(): Builder
    {
        return InstitutionUser::query()
            ->withGlobalScope('policy', InstitutionUserPolicy::scope())
            ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
            ->withoutGlobalScope(ExcludeArchivedInstitutionUsersScope::class);
    }
}
