<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstitutionUserListRequest;
use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;
use App\Policies\Scopes\InstitutionUserScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstitutionUserController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(InstitutionUserListRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewList', InstitutionUser::class);

        $institutionUsersQuery = InstitutionUser::query()->with([
            'user',
            'institutionUserRoles.role',
        ])->withGlobalScope('auth', new InstitutionUserScope);

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
            $query->where('status', $status);
        });

        return InstitutionUserResource::collection(
            $institutionUsersQuery->paginate(
                $request->validated('per_page', 10)
            )
        );
    }
}
