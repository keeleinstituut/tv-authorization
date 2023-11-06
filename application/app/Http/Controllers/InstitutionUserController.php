<?php

namespace App\Http\Controllers;

use App\Enums\InstitutionUserStatus;
use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\ActivateInstitutionUserRequest;
use App\Http\Requests\ArchiveInstitutionUserRequest;
use App\Http\Requests\DeactivateInstitutionUserRequest;
use App\Http\Requests\GetInstitutionUserRequest;
use App\Http\Requests\InstitutionUserListRequest;
use App\Http\Requests\UpdateCurrentInstitutionUserRequest;
use App\Http\Requests\UpdateInstitutionUserRequest;
use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use App\Models\Scopes\ExcludeArchivedInstitutionUsersScope;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Policies\InstitutionUserPolicy;
use App\Util\DateUtil;
use Arr;
use AuditLogClient\Services\AuditLogMessageBuilder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use League\Csv\ByteSequence;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Writer;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class InstitutionUserController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    #[OA\Get(
        path: '/institution-users/{institution_user_id}',
        parameters: [new OAH\UuidPath('institution_user_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionUserResource::class, description: 'Institution user with given UUID')]
    public function show(GetInstitutionUserRequest $request): InstitutionUserResource
    {
        $institutionUser = $this->getBaseQuery()->findOrFail($request->getInstitutionUserId());

        $this->authorize('view', $institutionUser);

        return new InstitutionUserResource($institutionUser);
    }

    /**
     * @throws AuthorizationException|Throwable
     */
    #[OA\Put(
        path: '/institution-users/{institution_user_id}',
        summary: 'Update the institution user with the given UUID',
        requestBody: new OAH\RequestBody(UpdateInstitutionUserRequest::class),
        parameters: [new OAH\UuidPath('institution_user_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionUserResource::class, description: 'Modified institution user')]
    public function update(UpdateInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            /** @var $institutionUser InstitutionUser */
            $institutionUser = $this->getBaseQuery()->findOrFail($request->getInstitutionUserId());

            $this->auditLogPublisher->publishModifyObjectAfterAction(
                $institutionUser,
                function () use ($institutionUser, $request) {
                    if ($request->hasAnyNonCalendarInput()) {
                        $this->authorize('update', $institutionUser);
                        $this->updateNonCalendarAttributes($institutionUser, $request->getValidatedNonCalendarInput());
                    }

                    if ($request->hasAnyWorktimeInput()) {
                        $this->authorize('updateWorktime', $institutionUser);
                        $institutionUser->fill($request->getValidatedWorktimeInput());
                    }

                    $institutionUser->saveOrFail();
                }
            );

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    /**
     * @throws AuthorizationException|Throwable
     */
    #[OA\Put(
        path: '/institution-users',
        summary: 'Update the active institution user received from JWT token',
        requestBody: new OAH\RequestBody(UpdateCurrentInstitutionUserRequest::class),
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionUserResource::class, description: 'Modified institution user')]
    public function updateCurrentInstitutionUser(UpdateCurrentInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            /** @var $institutionUser InstitutionUser */
            $institutionUser = $this->getBaseQuery()->findOrFail(Auth::user()->institutionUserId);

            $this->auditLogPublisher->publishModifyObjectAfterAction(
                $institutionUser,
                function () use ($request, $institutionUser) {
                    $institutionUser->fill($request->safe(['email', 'phone']));

                    if ($request->has('user')) {
                        $institutionUser->user->update($request->validated('user'));
                    }

                    $institutionUser->saveOrFail();
                }
            );

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    /**
     * @throws AuthorizationException
     * @throws CannotInsertRecord
     * @throws Exception
     * @throws Throwable
     * @throws ValidationException
     * @throws InvalidArgument
     */
    #[OA\Get(
        path: '/institution-users/export-csv',
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'CSV file of institution users in current institution (inferred from JWT)',
        content: new OA\MediaType(
            mediaType: 'text/csv',
            schema: new OA\Schema(type: 'string'),
            example: "Isikukood,Nimi,Meiliaadress,Telefoninumber,Üksus,Roll\n60104179950,\"Isabel Collier\",tmckenzie@buckridge.com,+3723843547,,\"Transportation Attendant, Electrician, Transportation and Material-Moving, Cutting Machine Operator\"\n34602284846,\"Mabelle Quitzon\",zlockman@tromp.com,+3727554275,,\"MARCOM Manager, Maintenance Equipment Operator, Locksmith\""
        )
    )]
    public function exportCsv(): StreamedResponse
    {
        $this->authorize('export', InstitutionUser::class);

        $csvDocument = Writer::createFromString()->setDelimiter(';')
            ->setOutputBOM(ByteSequence::BOM_UTF8);

        $csvDocument->insertOne([
            'Isikukood', 'Nimi', 'Meiliaadress', 'Telefoninumber', 'Üksus', 'Roll',
        ]);
        $csvDocument->insertAll(
            $this->getBaseQuery()
                ->with(['user', 'department', 'roles'])
                ->get()
                ->map(fn (InstitutionUser $institutionUser) => [
                    $institutionUser->user->personal_identification_code,
                    "{$institutionUser->user->forename} {$institutionUser->user->surname}",
                    $institutionUser->email,
                    $institutionUser->phone,
                    $institutionUser->department?->name,
                    $institutionUser->roles->map->name->join(', '),
                ])
        );

        $this->auditLogPublisher->publish(AuditLogMessageBuilder::makeUsingJWT()->toExportInstitutionUsers());

        return response()->streamDownload(
            $csvDocument->output(...),
            'exported_users.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * @throws AuthorizationException
     */
    #[OA\Get(
        path: '/institution-users',
        summary: 'List and optionally filter institution users belonging to the current institution (inferred from JWT)',
        parameters: [
            new OA\QueryParameter(name: 'fullname', schema: new OA\Schema(type: 'string', nullable: true)),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 10, enum: [10, 50, 100])),
            new OA\QueryParameter(name: 'sort_by', schema: new OA\Schema(type: 'string', enum: ['name', 'created_at'])),
            new OA\QueryParameter(name: 'sort_order', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\QueryParameter(
                name: 'roles',
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', format: 'uuid')
                )
            ),
            new OA\QueryParameter(
                name: 'statuses',
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', enum: InstitutionUserStatus::class)
                )
            ),
            new OA\QueryParameter(
                name: 'departments',
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', format: 'uuid')
                )
            ),
        ],
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\PaginatedCollectionResponse(itemsRef: InstitutionUserResource::class, description: 'Filtered institution users of current institution')]
    public function index(InstitutionUserListRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InstitutionUser::class);

        $institutionUsersQuery = $this->getBaseQuery()->select('institution_users.*')->with([
            'user',
            'institutionUserRoles.role',
        ]);

        $institutionUsersQuery->when(
            $request->validated('roles'),
            function (Builder $iuQuery, array $roles) {
                $iuQuery->whereHas(
                    'institutionUserRoles',
                    fn (Builder $iurClause) => $iurClause->whereIn('role_id', $roles)
                );
            }
        );

        $institutionUsersQuery->when(
            $request->validated('fullname'),
            function (Builder $iuQuery, string $fullName) {
                $iuQuery->whereRelation('user', function (Builder $uQuery) use ($fullName) {
                    $uQuery->where(DB::raw("CONCAT(forename, ' ', surname)"), 'ILIKE', "%$fullName%");
                });
            }
        );

        $sortOrder = $request->validated('sort_order', 'desc');
        $sortField = $request->validated('sort_by', 'created_at');
        $institutionUsersQuery->when(
            $sortField == 'name',
            function (Builder $iuQuery) use ($sortOrder) {
                $iuQuery->join('users', 'institution_users.user_id', '=', 'users.id')
                    ->orderBy('users.surname', $sortOrder)
                    ->orderBy('users.forename', $sortOrder);
            },
            function (Builder $iuQuery) use ($sortOrder, $sortField) {
                $iuQuery->orderBy(
                    $sortField,
                    $sortOrder
                );
            }
        );

        $institutionUsersQuery->when(
            $request->validated('statuses'),
            function (Builder $iuQuery, array $statuses) {
                $iuQuery->statusIn($statuses);
            }
        );

        $institutionUsersQuery->when(
            $request->validated('departments'),
            function (Builder $iuQuery, array $departments) {
                $iuQuery->whereIn('department_id', $departments);
            }
        );

        return InstitutionUserResource::collection(
            $institutionUsersQuery
                ->paginate($request->validated('per_page', 10))
                ->appends($request->validated())
        );
    }

    /** @throws AuthorizationException|Throwable */
    #[OA\Post(
        path: '/institution-users/deactivate',
        summary: 'Set a deactivation date for the specified institution user',
        requestBody: new OAH\RequestBody(DeactivateInstitutionUserRequest::class),
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionUserResource::class, description: 'Modified institution user')]
    public function deactivate(DeactivateInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            /** @var $institutionUser InstitutionUser */
            $institutionUser = $this->getBaseQuery()
                ->findOrFail($request->validated('institution_user_id'));

            $this->authorize('deactivate', $institutionUser);

            $this->auditLogPublisher->publishModifyObjectAfterAction(
                $institutionUser,
                function () use ($request, $institutionUser) {
                    $institutionUser->deactivation_date = $request->validated('deactivation_date');
                    $institutionUser->saveOrFail();

                    if ($request->getValidatedDeactivationDateAtEstonianMidnight()?->isSameDay(Date::today(DateUtil::ESTONIAN_TIMEZONE))) {
                        $institutionUser->institutionUserRoles()->each(fn (InstitutionUserRole $pivot) => $pivot->deleteOrFail());
                    }
                }
            );

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    /** @throws AuthorizationException|Throwable */
    #[OA\Post(
        path: '/institution-users/activate',
        summary: 'Reactivate a deactivated institution user with given UUID',
        requestBody: new OAH\RequestBody(ActivateInstitutionUserRequest::class),
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionUserResource::class, description: 'Modified institution user')]
    public function activate(ActivateInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            /** @var $institutionUser InstitutionUser */
            $institutionUser = $this->getBaseQuery()
                ->findOrFail($request->validated('institution_user_id'));

            $this->authorize('activate', $institutionUser);

            $this->auditLogPublisher->publishModifyObjectAfterAction(
                $institutionUser,
                function () use ($request, $institutionUser) {
                    $institutionUser->deactivation_date = null;
                    $institutionUser->saveOrFail();
                    $institutionUser->roles()->sync(
                        Role::findMany($request->validated('roles'))
                    );
                }
            );

            /** @noinspection PhpStatementHasEmptyBodyInspection */
            if (filter_var($request->validated('notify_user'), FILTER_VALIDATE_BOOLEAN)) {
                // TODO: queue email to user
            }

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    /** @throws AuthorizationException|Throwable */
    #[OA\Post(
        path: '/institution-users/archive',
        summary: 'Archive an institution user with the given UUID',
        requestBody: new OAH\RequestBody(ArchiveInstitutionUserRequest::class),
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: InstitutionUserResource::class, description: 'Modified institution user')]
    public function archive(ArchiveInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            /** @var $institutionUser InstitutionUser */
            $institutionUser = $this->getBaseQuery()
                ->findOrFail($request->validated('institution_user_id'));

            $this->authorize('archive', $institutionUser);

            $this->auditLogPublisher->publishModifyObjectAfterAction(
                $institutionUser,
                function () use ($institutionUser) {
                    $institutionUser->archived_at = Date::now();
                    $institutionUser->institutionUserRoles()->each(fn (InstitutionUserRole $pivot) => $pivot->deleteOrFail());
                    $institutionUser->saveOrFail();
                }
            );

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    public function getBaseQuery(): Builder
    {
        return InstitutionUser::getModel()
            ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
            ->withoutGlobalScope(ExcludeArchivedInstitutionUsersScope::class)
            ->withGlobalScope('policy', InstitutionUserPolicy::scope())
            ->whereHas('user');
    }

    /**
     * @throws Throwable
     */
    private function updateNonCalendarAttributes(InstitutionUser $institutionUser, array $validatedInput): void
    {
        $institutionUser->fill(Arr::only($validatedInput, ['email', 'phone']));

        if (Arr::has($validatedInput, 'user')) {
            $institutionUser->user->updateOrFail($validatedInput['user']);
        }
        if (Arr::has($validatedInput, 'roles')) {
            $institutionUser->roles()->sync($validatedInput['roles']);
        }
        if (Arr::has($validatedInput, 'department_id')) {
            $institutionUser->department()->associate($validatedInput['department_id']);
        }
    }
}
