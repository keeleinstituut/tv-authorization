<?php

namespace App\Http\Controllers;

use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\InstitutionUserVacationSyncRequest;
use App\Http\Resources\InstitutionUserVacationResource;
use App\Http\Resources\InstitutionUserVacationsResource;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserVacation;
use App\Models\InstitutionVacationExclusion;
use App\Policies\InstitutionUserPolicy;
use App\Policies\InstitutionUserVacationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InstitutionUserVacationController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    #[OA\Get(
        path: '/institution-user-vacations/{institution_user_id}',
        summary: 'List of vacations belonging to specified institution user',
        tags: ['Vacations'],
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\CollectionResponse(itemsRef: InstitutionUserVacationResource::class, description: 'Vacations of the institution user')]
    public function index(Request $request): InstitutionUserVacationsResource
    {
        $institutionUser = InstitutionUser::withGlobalScope('policy', InstitutionUserPolicy::scope())
            ->with([
                'activeInstitutionUserVacations',
                'activeInstitutionVacations',
                'activeInstitutionVacationExclusions',
            ])->findOrFail($request->route('institution_user_id'));

        $this->authorize('viewAny', [InstitutionUserVacation::class, $institutionUser]);

        return InstitutionUserVacationsResource::make($institutionUser);
    }

    /**
     * @throws AuthorizationException|Throwable
     */
    #[OA\Post(
        path: '/institution-user-vacations/sync',
        summary: 'Bulk create, delete and/or update institution user vacations.'.
        'If ID left unspecified, the vacation will be created. '.
        'If a previously existing vacation is not in request input, it will be deleted.',
        requestBody: new OAH\RequestBody(InstitutionUserVacationSyncRequest::class),
        tags: ['Vacations'],
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionUserVacationResource::class, description: 'Created vacation', response: Response::HTTP_CREATED)]
    public function sync(InstitutionUserVacationSyncRequest $request): InstitutionUserVacationsResource
    {
        $institutionUser = InstitutionUser::withGlobalScope('policy', InstitutionUserPolicy::scope())
            ->findOrFail($request->validated('institution_user_id'));

        $this->authorize('sync', [InstitutionUserVacation::class, $institutionUser]);

        return DB::transaction(function () use ($request, $institutionUser): InstitutionUserVacationsResource {
            $institutionUserId = $request->validated('institution_user_id');
            $vacationIds = collect($request->validated('vacations'))->map(function (array $vacationData) use ($institutionUserId) {
                // Update existing
                if (filled($id = data_get($vacationData, 'id'))) {
                    /** @var InstitutionUserVacation $vacation */
                    $vacation = $this->getBaseQuery()->where('institution_user_id', $institutionUserId)
                        ->findOrFail($id);

                    $vacation->fill([
                        'start_date' => data_get($vacationData, 'start_date'),
                        'end_date' => data_get($vacationData, 'end_date'),
                    ])->saveOrFail();

                    return $vacation->id;
                }
                // Create new
                $vacation = InstitutionUserVacation::make([
                    'institution_user_id' => $institutionUserId,
                    'start_date' => data_get($vacationData, 'start_date'),
                    'end_date' => data_get($vacationData, 'end_date'),
                ]);
                $vacation->saveOrFail();

                return $vacation->id;
            });

            // Delete missing
            $this->getBaseQuery()->where('institution_user_id', $request->validated('institution_user_id'))
                ->whereNotIn('id', $vacationIds)
                ->delete();

            // Save excluded institution vacations
            collect($request->validated('institution_vacation_exclusions'))->map(function ($institutionVacationId) use ($institutionUserId) {
                InstitutionVacationExclusion::firstOrNew([
                    'institution_vacation_id' => $institutionVacationId,
                    'institution_user_id' => $institutionUserId,
                ])->saveOrFail();
            });

            return InstitutionUserVacationsResource::make(
                $institutionUser->load([
                    'activeInstitutionUserVacations',
                    'activeInstitutionVacations',
                    'activeInstitutionVacationExclusions',
                ])
            );
        });
    }

    private function getBaseQuery(): Builder
    {
        return InstitutionUserVacation::query()
            ->withGlobalScope('policy', InstitutionUserVacationPolicy::scope())
            ->active();
    }
}
