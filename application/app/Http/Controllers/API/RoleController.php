<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\API\RoleCreateRequest;
use App\Http\Requests\API\RoleDeleteRequest;
use App\Http\Requests\API\RoleListRequest;
use App\Http\Requests\API\RoleShowRequest;
use App\Http\Requests\API\RoleUpdateRequest;
use App\Http\Resources\API\RoleResource;
use App\Models\Privilege;
use App\Models\Role;
use App\Policies\RolePolicy;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Services\AuditLogMessageBuilder;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/roles',
        summary: 'List existing roles',
        parameters: [
            new OA\QueryParameter(name: 'institution_id', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\CollectionResponse(itemsRef: RoleResource::class, description: 'Roles belonging to the specified institution')]
    public function index(RoleListRequest $request)
    {
        $params = collect($request->validated());

        $this->authorize('viewAny', Role::class);

        $query = $this->getBaseQuery()
            ->with('privileges');

        if ($institutionId = $params->get('institution_id')) {
            $query = $query->where('institution_id', $institutionId);
        }

        $data = $query->get();

        return RoleResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/roles',
        summary: 'Create a new role',
        requestBody: new OAH\RequestBody(RoleCreateRequest::class),
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: RoleResource::class, description: 'Created role', response: Response::HTTP_CREATED)]
    public function store(RoleCreateRequest $request)
    {
        $params = $request->validated();

        return DB::transaction(function () use ($params) {
            $role = new Role();
            $role->fill($params);
            $this->authorize('create', $role);

            $role->save();

            $privilegeKeys = $params['privileges'];
            $privileges = Privilege::whereIn('key', $privilegeKeys)->get();
            $privilegeIds = $privileges->pluck('id');
            $role->privileges()->sync($privilegeIds);

            $role->refresh();
            $role->load('privileges');

            $this->auditLogPublisher->publish(
                AuditLogMessageBuilder::makeUsingJWT()->toCreateObjectEvent(
                    AuditLogEventObjectType::Role,
                    $role->withoutRelations()->load('privileges')->toArray()
                )
            );

            return new RoleResource($role);
        });
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/roles/{role_id}',
        summary: 'Get information about the role with the given UUID',
        parameters: [new OAH\UuidPath('role_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\ResourceResponse(dataRef: RoleResource::class, description: 'Role with given UUID')]
    public function show(RoleShowRequest $request)
    {
        $id = $request->route('role_id');

        $role = $this->getBaseQuery()
            ->with('privileges')
            ->find($id) ?? abort(404);

        $this->authorize('view', $role);

        return new RoleResource($role);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/roles/{role_id}',
        summary: 'Update the role with the given UUID',
        requestBody: new OAH\RequestBody(RoleUpdateRequest::class),
        parameters: [new OAH\UuidPath('role_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: RoleResource::class, description: 'Updated role')]
    public function update(RoleUpdateRequest $request)
    {
        $id = $request->route('role_id');
        $role = $this->getBaseQuery()
            ->find($id) ?? abort(404);

        $this->authorize('update', $role);

        $roleBeforeChanges = $role->load('privileges')->toArray();
        $roleIdentitySubsetBeforeChanges = $role->withoutRelations()->getIdentitySubset();
        $params = $request->validated();

        return DB::transaction(function () use ($roleIdentitySubsetBeforeChanges, $roleBeforeChanges, $params, $role) {

            $role->fill($params);
            $role->save();

            $privilegeKeys = $params['privileges'];
            $privileges = Privilege::whereIn('key', $privilegeKeys)->get();
            $privilegeIds = $privileges->pluck('id');

            $role->privileges()->sync($privilegeIds);

            $role->refresh();
            $roleAfterChanges = $role->withoutRelations()->load('privileges')->toArray();

            $this->auditLogPublisher->publish(
                AuditLogMessageBuilder::makeUsingJWT()->toModifyObjectEventComputingDiff(
                    AuditLogEventObjectType::Role,
                    $roleIdentitySubsetBeforeChanges,
                    $roleBeforeChanges,
                    $roleAfterChanges
                )
            );

            return new RoleResource($role);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/roles/{role_id}',
        summary: 'Mark the role with the given UUID as deleted',
        parameters: [new OAH\UuidPath('role_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: RoleResource::class, description: 'Role which has been marked as deleted')]
    public function destroy(RoleDeleteRequest $request)
    {
        $id = $request->route('role_id');
        $role = $this->getBaseQuery()
            ->with('privileges')
            ->find($id) ?? abort(404);

        $this->authorize('delete', $role);

        return DB::transaction(function () use ($role) {
            $role->delete();

            $this->auditLogPublisher->publish(
                AuditLogMessageBuilder::makeUsingJWT()->toRemoveObjectEvent(
                    AuditLogEventObjectType::Role,
                    $role->getIdentitySubset()
                )
            );

            return new RoleResource($role);
        });
    }

    public function getBaseQuery()
    {
        return Role::getModel()->withGlobalScope('policy', RolePolicy::scope());
    }
}
