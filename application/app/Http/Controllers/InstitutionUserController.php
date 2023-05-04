<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Http\Controllers;

use App\Http\Requests\GetInstitutionUserRequest;
use App\Http\Requests\UpdateInstitutionUserRequest;
use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;
use Auth;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InstitutionUserController extends Controller
{
    public function show(GetInstitutionUserRequest $request): InstitutionUserResource
    {
        return new InstitutionUserResource(
            $this->getBaseQuery()->findOrFail($request->getInstitutionUserId())
        );
    }

    public function update(UpdateInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            $institutionUser = $this->getBaseQuery()->findOrFail($request->getInstitutionUserId());

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
        return InstitutionUser::getModel()->where('institution_id', Auth::user()->institutionId);
    }
}
