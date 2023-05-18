<?php

namespace App\Http\Controllers;

use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

class InstitutionController extends Controller
{
    public function index(): ResourceCollection
    {
        $personalIdentificationCode = Auth::user()?->personalIdentificationCode;

        abort_if(empty($personalIdentificationCode), 401);

        return InstitutionResource::collection(
            Institution::queryByUserPersonalIdentificationCode($personalIdentificationCode)->get()
        );
    }
}
