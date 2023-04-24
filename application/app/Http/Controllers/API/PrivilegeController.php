<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PrivilegeListRequest;
use App\Http\Resources\API\PrivilegeResource;
use App\Models\Privilege;

class PrivilegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PrivilegeListRequest $request)
    {
        $data = Privilege::getModel()->get();

        return PrivilegeResource::collection($data);
    }
}
