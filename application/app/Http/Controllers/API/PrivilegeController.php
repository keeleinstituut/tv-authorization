<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PrivilegeListRequest;
use App\Http\Resources\API\PrivilegeResource;
use App\Models\Privilege;
use App\Policies\PrivilegePolicy;

class PrivilegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
