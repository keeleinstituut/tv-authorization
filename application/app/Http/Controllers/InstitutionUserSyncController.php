<?php

namespace App\Http\Controllers;

use App\Http\Resources\Sync\InstitutionUserSyncResource;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use KeycloakAuthGuard\Middleware\EnsureJwtBelongsToServiceAccountWithSyncRole;

class InstitutionUserSyncController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureJwtBelongsToServiceAccountWithSyncRole::class);
    }

    public function index(): AnonymousResourceCollection
    {
        return InstitutionUserSyncResource::collection(
            InstitutionUser::withTrashed()->paginate(1000)
        );
    }

    public function show(Request $request): InstitutionUserSyncResource
    {
        return new InstitutionUserSyncResource(
            InstitutionUser::withTrashed()->whereId($request->route('id'))->firstOrFail()
        );
    }
}
