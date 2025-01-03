<?php

namespace App\Http\Controllers;

use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use App\Policies\InstitutionPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class InstitutionController extends Controller
{
    #[OA\Get(
        path: '/institutions',
        summary: 'List institutions that the current user belongs to (current user inferred from JWT)',
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\CollectionResponse(itemsRef: InstitutionResource::class, description: 'Institutions that the current user belongs to')]
    public function index(): ResourceCollection
    {
        $personalIdentificationCode = Auth::user()?->personalIdentificationCode;

        abort_if(empty($personalIdentificationCode), 401);

        return InstitutionResource::collection(
            Institution::queryByUserPersonalIdentificationCode($personalIdentificationCode)->get()
        );
    }

    /** @throws AuthorizationException */
    #[OA\Get(
        path: '/institutions/{institution_id}',
        parameters: [new OAH\UuidPath('institution_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionResource::class, description: 'Institution with given UUID')]
    public function show(Request $request): InstitutionResource
    {
        $id = $request->route('institution_id');
        $institution = Institution::getModel()->findOrFail($id);

        try {
            $this->authorize('view', $institution);
            return new InstitutionResource($institution);
        } catch (AuthorizationException $e) {
            return (new InstitutionResource($institution))->publicRepresentation();
        }

    }

    /** @throws AuthorizationException|Throwable */
    #[OA\Put(
        path: '/institutions',
        summary: 'Update the institution with the given UUID',
        requestBody: new OAH\RequestBody(UpdateInstitutionRequest::class),
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionResource::class, description: 'Modified institution')]
    public function update(UpdateInstitutionRequest $request): InstitutionResource
    {
        $id = $request->route('institution_id');
        /** @var Institution $institution */
        $institution = $this->getBaseQuery()->findOrFail($id);

        if ($request->hasWorktimeAttributes()) {
            $this->authorize('updateWorktime', $institution);
        }

        if ($request->hasInstitutionMainAttributes()) {
            $this->authorize('update', $institution);
        }

        $this->auditLogPublisher->publishModifyObjectAfterAction(
            $institution,
            function () use ($request, $institution) {
                $institution->fill($request->validated())->saveOrFail();
            }
        );

        return new InstitutionResource($institution->refresh());
    }

    /** @throws AuthorizationException */
    #[OA\Get(
        path: '/institutions/{institution_id}/logo',
        parameters: [new OAH\UuidPath('institution_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized]
    )]
    public function logo(Request $request): ?StreamedResponse
    {
        $id = $request->route('institution_id');
        $institution = $this->getBaseQuery()->findOrFail($id);

        $this->authorize('view', $institution);

        if ($logoMedia = $institution->getMedia('logo')->first()) {
            return $logoMedia->toInlineResponse($request);
        }
        abort(404);
    }


    public function getBaseQuery(): Builder
    {
        return Institution::getModel()->withGlobalScope('policy', InstitutionPolicy::scope());
    }
}
