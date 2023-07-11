<?php

namespace App\Http\Controllers;

use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Institution;
use App\Policies\DepartmentPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DepartmentController extends Controller
{
    #[OA\Get(
        path: '/departments',
        summary: 'List departments belonging to institution (institution inferred from JWT)',
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\CollectionResponse(itemsRef: DepartmentResource::class, description: 'Departments of institution (institution inferred from JWT)')]
    public function index(): ResourceCollection
    {
        return DepartmentResource::collection($this->getBaseQuery()->get());
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    #[OA\Post(
        path: '/departments',
        summary: 'Create a new department',
        requestBody: new OAH\RequestBody(StoreDepartmentRequest::class),
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: DepartmentResource::class, description: 'Created department', response: Response::HTTP_CREATED)]
    public function store(StoreDepartmentRequest $request): DepartmentResource
    {
        $this->authorize('create', Department::class);

        return DB::transaction(function () use ($request): DepartmentResource {
            $currentInstitution = Institution::findOrFail(Auth::user()->institutionId);

            $newDepartment = new Department($request->validated());
            $newDepartment->institution()->associate($currentInstitution);
            $newDepartment->saveOrFail();

            return new DepartmentResource($newDepartment->refresh());
        });
    }

    /**
     * @throws AuthorizationException
     */
    #[OA\Get(
        path: '/departments/{department_id}',
        summary: 'Get information about department with the given UUID',
        parameters: [new OAH\UuidPath('department_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\ResourceResponse(dataRef: DepartmentResource::class, description: 'Department with given UUID')]
    public function show(Request $request): DepartmentResource
    {
        $id = $request->route('department_id');
        $department = $this->getBaseQuery()->findOrFail($id);

        $this->authorize('view', $department);

        return new DepartmentResource($department);
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    #[OA\Put(
        path: '/departments/{department_id}',
        summary: 'Update the department with the given UUID',
        requestBody: new OAH\RequestBody(UpdateDepartmentRequest::class),
        parameters: [new OAH\UuidPath('department_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OAH\ResourceResponse(dataRef: DepartmentResource::class, description: 'Updated department')]
    public function update(UpdateDepartmentRequest $request): DepartmentResource
    {
        return DB::transaction(function () use ($request): DepartmentResource {
            $id = $request->route('department_id');
            $department = $this->getBaseQuery()->findOrFail($id);

            $this->authorize('update', $department);

            $department->fill($request->validated())->saveOrFail();

            return new DepartmentResource($department->refresh());
        });
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    #[OA\Delete(
        path: '/departments/{department_id}',
        summary: 'Mark the department with the given UUID as deleted',
        parameters: [new OAH\UuidPath('department_id')],
        responses: [new OAH\NotFound, new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\ResourceResponse(dataRef: DepartmentResource::class, description: 'The department marked as deleted')]
    public function destroy(Request $request): DepartmentResource
    {
        return DB::transaction(function () use ($request): DepartmentResource {
            $id = $request->route('department_id');

            /** @var Department $department */
            $department = $this->getBaseQuery()->findOrFail($id);

            $this->authorize('delete', $department);

            $department->institutionUsers()->update(['department_id' => null]);
            $department->deleteOrFail();

            return new DepartmentResource($department->refresh());
        });
    }

    public function getBaseQuery(): Builder
    {
        return Department::getModel()
            ->withGlobalScope('policy', DepartmentPolicy::scope())
            ->whereHas('institution');
    }
}
