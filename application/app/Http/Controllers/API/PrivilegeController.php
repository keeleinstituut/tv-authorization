<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\API\PrivilegeListRequest;
use App\Http\Resources\API\PrivilegeResource;
use App\Models\Privilege;
use App\Policies\PrivilegePolicy;
use OpenApi\Attributes as OA;

class PrivilegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/privileges',
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\CollectionResponse(itemsRef: PrivilegeResource::class, description: 'All privileges used in Tõlkevärav')]
    public function index(PrivilegeListRequest $request)
    {
        $this->authorize('viewAny', Privilege::class);

        $data = $this->getBaseQuery()->get();

        return PrivilegeResource::collection($data);
    }

    public function getBaseQuery()
    {
        return Privilege::getModel()->withGlobalScope('policy', PrivilegePolicy::scope());
    }
}
