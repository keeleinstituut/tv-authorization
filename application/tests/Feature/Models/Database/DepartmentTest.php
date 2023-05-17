<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Tests\Feature\Models\Database;

use App\Models\Department;
use App\Models\Institution;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $createdModel = Department::factory()->create();
        $this->assertModelExists($createdModel);

        $retrievedModel = Department::findOrFail($createdModel->id);
        $this->assertEquals($createdModel->name, $retrievedModel->name);
    }

    public function test_soft_deletion(): void
    {
        $createdModel = Department::factory()->create();
        $createdModel->deleteOrFail();
        $this->assertSoftDeleted($createdModel);
        $this->assertEmpty(Department::find($createdModel->id));
    }

    public function test_duplicate_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $referenceInstitution = Institution::factory()->create();

        Department::factory()
            ->count(2)
            ->for($referenceInstitution)
            ->create([
                'name' => 'Some name',
            ]);
    }

    public function test_soft_deleted_departments_dont_count_as_duplicates(): void
    {
        $referenceInstitution = Institution::factory()->create();
        $referenceDepartmentName = 'Testüksus';

        $firstDepartment = Department::factory()
            ->for($referenceInstitution)
            ->create(['name' => $referenceDepartmentName]);

        $firstDepartment->deleteOrFail();

        $secondDepartment = Department::factory()
            ->for($referenceInstitution)
            ->create(['name' => $referenceDepartmentName]);

        $this->assertModelExists($secondDepartment);
    }
}
