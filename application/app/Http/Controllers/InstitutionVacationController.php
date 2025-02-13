<?php

namespace App\Http\Controllers;

use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\InstitutionVacationSyncRequest;
use App\Http\Resources\InstitutionVacationResource;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionVacation;
use App\Policies\InstitutionVacationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
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
        path: '/institution-vacations/sync',
        summary: 'Bulk create, delete and/or update institution vacations.'.
        'If ID left unspecified, the vacation will be created. '.
        'If a previously existing vacation is not in request input, it will be deleted.',
        requestBody: new OAH\RequestBody(InstitutionVacationSyncRequest::class),
        tags: ['Vacations'],
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionVacationResource::class, description: 'Affected vacations', response: Response::HTTP_OK)]
    public function sync(InstitutionVacationSyncRequest $request): AnonymousResourceCollection
    {
        $institutionId = Auth::user()->institutionId;

        /** @var Institution $institution */
        $institution = $this->getInstitutionBaseQuery()->findOrFail($institutionId);

        return $this->auditLogPublisher->publishModifyObjectAfterAction(
            $institution,
            function () use ($request, $institution) {
                $this->authorize('sync', InstitutionVacation::class);

                return DB::transaction(function () use ($request, $institution): AnonymousResourceCollection {
                    $vacationIds = collect($request->validated('vacations'))->map(function (array $vacationData) use ($institution) {
                        // Update existing
                        if (filled($id = data_get($vacationData, 'id'))) {
                            /** @var InstitutionVacation $vacation */
                            $vacation = $this->getBaseQuery()->where('institution_id', $institution->id)
                                ->findOrFail($id);

                            $vacation->fill([
                                'start_date' => data_get($vacationData, 'start_date'),
                                'end_date' => data_get($vacationData, 'end_date'),
                            ])->saveOrFail();

                            return $vacation->id;
                        }

                        // Create new
                        $vacation = InstitutionVacation::make([
                            'institution_id' => $institution->id,
                            'start_date' => data_get($vacationData, 'start_date'),
                            'end_date' => data_get($vacationData, 'end_date'),
                        ]);
                        $vacation->saveOrFail();

                        return $vacation->id;
                    });

                    // Delete missing
                    $this->getBaseQuery()->whereNotIn('id', $vacationIds)
                        ->delete();

                    return InstitutionVacationResource::collection(
                        $this->getBaseQuery()
                            ->whereIn('id', $vacationIds)
                            ->orderBy('start_date')
                            ->get()
                    );
                });
            }
        );
    }

    private function getBaseQuery(): Builder
    {
        return InstitutionVacation::getModel()
            ->withGlobalScope('policy', InstitutionVacationPolicy::scope())
            ->whereHas('institution')
            ->active();
    }

    private function getInstitutionBaseQuery(): Builder
    {
        return Institution::query();
    }
}
