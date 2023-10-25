<?php

namespace App\Http\Controllers;

use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use App\Policies\InstitutionPolicy;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Services\AuditLogMessageBuilder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
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
        $institution = $this->getBaseQuery()->findOrFail($id);

        $this->authorize('view', $institution);

        return new InstitutionResource($institution);
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
        $institution = $this->getBaseQuery()->findOrFail($id);

        $this->authorize('update', $institution);

        $institutionBeforeChanges = $institution->withoutRelations()->toArray();
        $institutionIdentitySubsetBeforeChanges = $institution->getIdentitySubset();

        $institution->fill($request->validated())->saveOrFail();

        $institutionAfterChanges = $institution->withoutRelations()->toArray();

        $this->auditLogPublisher->publish(
            AuditLogMessageBuilder::makeUsingJWT()->toModifyObjectEventComputingDiff(
                AuditLogEventObjectType::Institution,
                $institutionIdentitySubsetBeforeChanges,
                $institutionBeforeChanges,
                $institutionAfterChanges
            )
        );

        return new InstitutionResource($institution->refresh());
    }

    public function getBaseQuery(): Builder
    {
        return Institution::getModel()->withGlobalScope('policy', InstitutionPolicy::scope());
    }
}
