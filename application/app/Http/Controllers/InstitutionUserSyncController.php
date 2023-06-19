<?php

namespace App\Http\Controllers;

use App\Http\Resources\Sync\InstitutionUserSyncResource;
use App\Models\InstitutionUser;
use App\Models\Scopes\ExcludeArchivedInstitutionUsersScope;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Models\Scopes\ExcludeIfRelatedUserSoftDeletedScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use KeycloakAuthGuard\Middleware\EnsureJwtBelongsToServiceAccountWithSyncRole;

class InstitutionUserSyncController extends Controller
{
    const PER_PAGE = 1000;

    public function __construct()
    {
        $this->middleware(EnsureJwtBelongsToServiceAccountWithSyncRole::class);
    }

    public function index(): AnonymousResourceCollection
    {
        return InstitutionUserSyncResource::collection(
            $this->getBaseQuery()->paginate(self::PER_PAGE)
        );
    }

    public function show(Request $request): InstitutionUserSyncResource
    {
        return new InstitutionUserSyncResource(
            $this->getBaseQuery()->where('id', $request->route('id'))->firstOrFail()
        );
    }

    private function getBaseQuery(): Builder
    {
        return InstitutionUser::withTrashed()
            ->withoutGlobalScope(ExcludeArchivedInstitutionUsersScope::class)
            ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
            ->withoutGlobalScope(ExcludeIfRelatedUserSoftDeletedScope::class);
    }
}
