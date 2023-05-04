<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\RoleCreateRequest;
use App\Http\Requests\API\RoleDeleteRequest;
use App\Http\Requests\API\RoleListRequest;
use App\Http\Requests\API\RoleShowRequest;
use App\Http\Requests\API\RoleUpdateRequest;
use App\Http\Resources\API\RoleResource;
use App\Models\Privilege;
use App\Models\Role;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RoleListRequest $request)
    {
        $params = $request->validated();

        $query = $this->getBaseQuery()
            ->with('privileges');

        if ($institutionId = $params['institution_id']) {
            $query = $query->where('institution_id', $institutionId);
        }

        $data = $query->get();

        return RoleResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleCreateRequest $request)
    {
        $params = $request->validated();

        return DB::transaction(function () use ($params) {
            $role = new Role();
            $role->fill($params);
            $role->save();

            $privilegeKeys = $params['privileges'];
            $privileges = Privilege::whereIn('key', $privilegeKeys)->get();
            $privilegeIds = $privileges->pluck('id');
            $role->privileges()->sync($privilegeIds);

            $role->refresh();
            $role->load('privileges');

            // TODO: add auditlog creation
            return new RoleResource($role);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(RoleShowRequest $request)
    {
        $id = $request->route('role_id');

        $role = $this->getBaseQuery()
            ->with('privileges')
            ->find($id) ?? abort(404);

        return new RoleResource($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleUpdateRequest $request)
    {
        $id = $request->route('role_id');
        $role = $this->getBaseQuery()
            ->find($id) ?? abort(404);

        $params = $request->validated();

        return DB::transaction(function () use ($params, $role) {
            $role->fill($params);
            $role->save();

            $privilegeKeys = $params['privileges'];
            $privileges = Privilege::whereIn('key', $privilegeKeys)->get();
            $privilegeIds = $privileges->pluck('id');

            $role->privileges()->sync($privilegeIds);

            $role->refresh();
            $role->load('privileges');

            // TODO: add auditlog creation
            return new RoleResource($role);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoleDeleteRequest $request)
    {
        $id = $request->route('role_id');
        $role = $this->getBaseQuery()
            ->with('privileges')
            ->find($id) ?? abort(404);

        return DB::transaction(function () use ($role) {
            $role->delete();

            // TODO: add auditlog creation
            return new RoleResource($role);
        });
    }

    public function getBaseQuery()
    {
        return Role::getModel()->withGlobalScope('policy', RolePolicy::scope());
    }
}