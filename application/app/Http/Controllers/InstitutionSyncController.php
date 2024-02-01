<?php

namespace App\Http\Controllers;

use App\Http\Resources\Sync\InstitutionSyncResource;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use KeycloakAuthGuard\Middleware\EnsureJwtBelongsToServiceAccountWithSyncRole;

class InstitutionSyncController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureJwtBelongsToServiceAccountWithSyncRole::class);
    }

    public function index(): AnonymousResourceCollection
    {
        return InstitutionSyncResource::collection(
            Institution::withTrashed()->with('vacations')->get()
        );
    }

    public function show(Request $request): InstitutionSyncResource
    {
        return new InstitutionSyncResource(
            Institution::withTrashed()->with('vacations')->whereId($request->route('id'))
                ->firstOrFail()
        );
    }
}
