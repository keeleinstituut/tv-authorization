<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use App\Policies\InstitutionPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class InstitutionController extends Controller
{
    public function index(): ResourceCollection
    {
        $personalIdentificationCode = Auth::user()?->personalIdentificationCode;

        abort_if(empty($personalIdentificationCode), 401);

        return InstitutionResource::collection(
            Institution::queryByUserPersonalIdentificationCode($personalIdentificationCode)->get()
        );
    }

    /** @throws AuthorizationException */
    public function show(Request $request): InstitutionResource
    {
        $id = $request->route('institution_id');
        $institution = $this->getBaseQuery()->findOrFail($id);

        $this->authorize('view', $institution);

        return new InstitutionResource($institution);
    }

    /** @throws AuthorizationException|Throwable */
    public function update(UpdateInstitutionRequest $request): InstitutionResource
    {
        $id = $request->route('institution_id');
        $institution = $this->getBaseQuery()->findOrFail($id);

        $this->authorize('update', $institution);

        $institution->fill($request->validated())->saveOrFail();

        return new InstitutionResource($institution->refresh());
    }

    public function getBaseQuery(): Builder
    {
        return Institution::getModel()->withGlobalScope('policy', InstitutionPolicy::scope());
    }
}
