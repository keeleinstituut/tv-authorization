<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstitutionVacationStoreRequest;
use App\Http\Requests\InstitutionVacationUpdateRequest;
use App\Http\Resources\InstitutionVacationResource;
use App\Models\Institution;
use App\Models\InstitutionVacation;
use App\Policies\InstitutionVacationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\OpenApiHelpers as OAH;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InstitutionVacationController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    #[OA\Get(
        path: '/institution-vacations',
        summary: 'List of vacations belonging to institution (institution inferred from JWT)',
        tags: ['Vacations'],
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\CollectionResponse(itemsRef: InstitutionVacationResource::class, description: 'Vacations of institution (institution inferred from JWT)')]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InstitutionVacation::class);

        return InstitutionVacationResource::collection(
            $this->getBaseQuery()->orderBy('start_date')->get()
        );
    }

    /**
     * @throws AuthorizationException|Throwable
     */
    #[OA\Post(
        path: '/institution-vacations',
        summary: 'Create a new vacation',
        requestBody: new OAH\RequestBody(InstitutionVacationStoreRequest::class),
        tags: ['Vacations'],
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionVacationResource::class, description: 'Created vacation', response: Response::HTTP_CREATED)]
    public function store(InstitutionVacationStoreRequest $request): InstitutionVacationResource
    {
        $this->authorize('create', InstitutionVacation::class);

        return DB::transaction(function () use ($request): InstitutionVacationResource {
            $currentInstitution = Institution::findOrFail(Auth::user()->institutionId);

            $vacation = (new InstitutionVacation)->fill($request->validated());
            $vacation->institution()->associate($currentInstitution);
            $vacation->saveOrFail();

            return InstitutionVacationResource::make($vacation->refresh());
        });
    }

    /**
     * @throws AuthorizationException
     */
    #[OA\Get(
        path: '/institution-vacations/{institution_vacation_id}',
        summary: 'Get information about institution vacation with the given UUID',
        tags: ['Vacations'],
        parameters: [new OAH\UuidPath('institution_vacation_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionVacationResource::class, description: 'Institution vacation with given UUID')]
    public function show(Request $request): InstitutionVacationResource
    {
        $vacation = $this->getBaseQuery()->findOrFail(
            $request->route('institution_vacation_id')
        );

        $this->authorize('view', $vacation);

        return new InstitutionVacationResource($vacation);
    }

    /**
     * @throws Throwable
     */
    #[OA\Put(
        path: '/institution-vacations/{institution_vacation_id}',
        summary: 'Update the institution vacation with the given UUID',
        requestBody: new OAH\RequestBody(InstitutionVacationUpdateRequest::class),
        tags: ['Vacations'],
        parameters: [new OAH\UuidPath('institution_vacation_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionVacationResource::class, description: 'Updated institution vacation')]
    public function update(InstitutionVacationUpdateRequest $request): InstitutionVacationResource
    {
        return DB::transaction(function () use ($request): InstitutionVacationResource {
            $vacation = $this->getBaseQuery()->findOrFail(
                $request->getInstitutionVacationId()
            );

            $this->authorize('update', $vacation);

            $vacation->fill($request->validated())->saveOrFail();

            return new InstitutionVacationResource($vacation->refresh());
        });
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    #[OA\Delete(
        path: '/institution-vacations/{institution_vacation_id}',
        summary: 'Mark the institution vacation with the given UUID as deleted',
        tags: ['Vacations'],
        parameters: [new OAH\UuidPath('institution_vacation_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionVacationResource::class, description: 'The institution vacation marked as deleted')]
    public function destroy(Request $request): InstitutionVacationResource
    {
        return DB::transaction(function () use ($request): InstitutionVacationResource {
            /** @var InstitutionVacation $vacation */
            $vacation = $this->getBaseQuery()->findOrFail(
                $request->route('institution_vacation_id')
            );

            $this->authorize('delete', $vacation);
            $vacation->deleteOrFail();

            return new InstitutionVacationResource($vacation->refresh());
        });
    }

    private function getBaseQuery(): Builder
    {
        return InstitutionVacation::getModel()
            ->withGlobalScope('policy', InstitutionVacationPolicy::scope())
            ->whereHas('institution');
    }
}
