<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetInstitutionUserRequest;
use App\Http\Requests\UpdateInstitutionUserRequest;
use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;
use App\Policies\InstitutionUserPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class InstitutionUserController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function show(GetInstitutionUserRequest $request): InstitutionUserResource
    {
        $institutionUser = $this->getBaseQuery()->findOrFail($request->getInstitutionUserId());

        $this->authorize('view', $institutionUser);

        return new InstitutionUserResource($institutionUser);
    }

    /**
     * @throws AuthenticationException|Throwable
     */
    public function update(UpdateInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            /** @var $institutionUser InstitutionUser */
            $institutionUser = $this->getBaseQuery()->findOrFail($request->getInstitutionUserId());

            $this->authorize('update', $institutionUser);

            $institutionUser->fill($request->safe(['email', 'phone']));

            if ($request->has('user')) {
                $institutionUser->user->update($request->validated('user'));
            }
            if ($request->has('roles')) {
                $institutionUser->roles()->sync($request->validated('roles'));
            }
            if ($request->has('department_id')) {
                $institutionUser->department()->associate($request->validated('department_id'));
            }

            $institutionUser->save();
            // TODO: audit log

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    public function getBaseQuery(): Builder
    {
        return InstitutionUser::getModel()->withGlobalScope('policy', InstitutionUserPolicy::scope());
    }
}
