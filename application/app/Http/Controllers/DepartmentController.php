<?php

namespace App\Http\Controllers;

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
use Throwable;

class DepartmentController extends Controller
{
    public function index(): ResourceCollection
    {
        return DepartmentResource::collection($this->getBaseQuery()->get());
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(StoreDepartmentRequest $request): DepartmentResource
    {
        return DB::transaction(function () use ($request): DepartmentResource {
            $this->authorize('create', Department::class);

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
