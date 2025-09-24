<?php

namespace App\Http\Controllers;

use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\API\DepartmentBulkUpdateRequest;
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
        $data = $this->getBaseQuery()->orderBy('name')->get();
        return DepartmentResource::collection($data);
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

    public function bulkUpdate(DepartmentBulkUpdateRequest $request)
    {
        $params = collect($request->validated());

        $this->authorize('bulkUpdate', Department::class);

        $data = collect($params->get('data'));

        return DB::transaction(function () use ($data) {
            $currentDepartments = $this->getBaseQuery()->get();

            $remainingDepartments = $data->map(function ($element) use ($currentDepartments) {
                if ($id = data_get($element, 'id')) {
                    $existingDepartment = $currentDepartments->firstWhere('id', $id);
                    $existingDepartment->name = $element['name'];
                    return $existingDepartment;
                } else {
                    $newDepartment = new Department();
                    $newDepartment->name = $element['name'];
                    $newDepartment->institution_id = Auth::user()->institutionId;
                    return $newDepartment;
                }
            });

            // Save departments
            $remainingDepartments->each(function ($department) {
                $department->save();
            });

            // Delete existing departments that are not in $departments array.
            $currentDepartments
                ->filter(function ($department) use ($remainingDepartments) {
                    return !$remainingDepartments->firstWhere('id', $department->id);
                })
                ->each(function ($department) {
                    $department->delete();
                });


            $departments = $this->getBaseQuery()->orderBy('name')->get();

            return DepartmentResource::collection($departments);
        });
    }

    public function getBaseQuery(): Builder
    {
        return Department::getModel()
            ->withGlobalScope('policy', DepartmentPolicy::scope())
            ->whereHas('institution');
    }
}
