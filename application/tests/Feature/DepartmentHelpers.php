<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Institution;
use Closure;
use Illuminate\Support\Collection;

final readonly class DepartmentHelpers
{
    /**
     * @param  Closure(Department):void|null  ...$modifiers
     * @return Collection<Department>
     */
    public static function createModifiableDepartmentsInSameInstitution(Institution $institution = null, ?Closure ...$modifiers): Collection
    {
        $institution = $institution ?? Institution::factory()->create();

        return collect($modifiers)
            ->map(function (?Closure $modify) use ($institution) {
                $department = Department::factory()
                    ->for($institution)
                    ->create();

                if (filled($modify)) {
                    $modify($department);
                    $department->saveOrFail();
                }

                return $department->refresh();
            });
    }
}
