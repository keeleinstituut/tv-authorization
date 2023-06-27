<?php

namespace App\Http\Controllers;

use App\Http\OpenApiHelpers as OAH;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class InstitutionController extends Controller
{
    #[OA\Get(
        path: '/institutions',
        summary: 'List institutions that the current user belongs to (current user inferred from JWT)',
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\CollectionResponse(itemsRef: InstitutionResource::class, description: 'Institutions that the current user belongs to')]
    public function index(): ResourceCollection
    {
        $personalIdentificationCode = Auth::user()?->personalIdentificationCode;

        abort_if(empty($personalIdentificationCode), 401);

        return InstitutionResource::collection(
            Institution::queryByUserPersonalIdentificationCode($personalIdentificationCode)->get()
        );
    }
}
