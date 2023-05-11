<?php

namespace App\Http\Controllers;

use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstitutionUserSyncController extends Controller
{
    /**
     * TODO: add endpoint protection from public access
     */
    public function index(): AnonymousResourceCollection
    {
        return InstitutionUserResource::collection(
            InstitutionUser::query()->paginate(1000)
        );
    }
    /**
     * TODO: add endpoint protection from public access
     */
    public function show(Request $request): InstitutionUserResource
    {
        return new InstitutionUserResource(
            InstitutionUser::whereId($request->route('id'))->firstOrFail()
        );
    }
}
