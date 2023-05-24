<?php

namespace App\Http\Controllers;

use App\Enums\InstitutionUserStatus;
use App\Enums\PrivilegeKey;
use App\Http\Requests\DeactivateInstitutionUserRequest;
use App\Http\Requests\GetInstitutionUserRequest;
use App\Http\Requests\InstitutionUserListRequest;
use App\Http\Requests\UpdateInstitutionUserRequest;
use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Policies\InstitutionUserPolicy;
use App\Util\DateUtil;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
     * @throws AuthorizationException|Throwable
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

            $institutionUser->saveOrFail();
            // TODO: audit log

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    /**
     * @throws AuthorizationException|CannotInsertRecord|Exception
     */
    public function exportCsv(): StreamedResponse
    {
        $this->authorize('export', InstitutionUser::class);

        $csvDocument = Writer::createFromString();

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

        // TODO: audit log

        return response()->streamDownload(
            $csvDocument->output(...),
            'exported_users.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function index(InstitutionUserListRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InstitutionUser::class);

        $institutionUsersQuery = $this->getBaseQuery()->with([
            'user',
            'institutionUserRoles.role',
        ]);

        $roleId = $request->validated('role_id');
        $institutionUsersQuery->when($roleId, function (Builder $query, string $roleId) {
            $query->whereHas(
                'institutionUserRoles',
                fn (Builder $roleQuery) => $roleQuery->where('role_id', $roleId)
            );
        });

        $sortOrder = $request->validated('sort_order', 'desc');
        $sortField = $request->validated('sort_by', 'created_at');
        $institutionUsersQuery->when(
            $sortField == 'name',
            function (Builder $query) use ($sortOrder) {
                $query->join('users', 'institution_users.user_id', '=', 'users.id')
                    ->orderBy('users.surname', $sortOrder)
                    ->orderBy('users.forename', $sortOrder);
            },
            function (Builder $query) use ($sortOrder, $sortField) {
                $query->orderBy(
                    $sortField,
                    $sortOrder
                );
            }
        );

        $status = $request->validated('status');
        $institutionUsersQuery->when($status, function (Builder $query, string $status) {
            $query->status(InstitutionUserStatus::from($status));
        });

        return InstitutionUserResource::collection(
            $institutionUsersQuery->paginate(
                $request->validated('per_page', 10)
            )
        );
    }

    /** @throws AuthorizationException|Throwable */
    public function deactivate(DeactivateInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            /** @var $institutionUser InstitutionUser */
            $institutionUser = $this->getBaseQuery()
                ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
                ->findOrFail($request->validated('institution_user_id'));

            $this->authorize('deactivate', $institutionUser);

            if ($request->validated('deactivation_date') === null) {
                Gate::allowIf(Auth::hasPrivilege(PrivilegeKey::ActivateUser->value));
            }

            $institutionUser->deactivation_date = $request->validated('deactivation_date');
            $institutionUser->saveOrFail();

            if ($request->getValidatedDeactivationDateAtEstonianMidnight()?->isSameDay(DateUtil::estonianNow())) {
                $institutionUser->institutionUserRoles()->delete();
            }

            // TODO: audit log

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    public function getBaseQuery(): Builder
    {
        return InstitutionUser::getModel()
            ->withGlobalScope('policy', InstitutionUserPolicy::scope())
            ->whereHas('user');
    }
}
