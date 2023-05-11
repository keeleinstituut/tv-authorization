<?php

namespace App\Http\Controllers;

use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstitutionSyncController extends Controller
{
    /**
     * TODO: add endpoint protection from public access
     */
    public function index(): AnonymousResourceCollection
    {
        return InstitutionResource::collection(
            Institution::all()
        );
    }

    public function show(Request $request): InstitutionResource
    {
        return new InstitutionResource(
            Institution::whereId($request->route('id'))->firstOrFail()
        );
    }
}
