<?php

namespace App\Http\Controllers;

use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;

class InstitutionUserSyncController extends Controller
{
    /**
     * TODO: add endpoint protection from public access
     */
    public function index()
    {
        InstitutionUserResource::collection(
            InstitutionUser::query()->paginate(1000)
        );
    }
}
