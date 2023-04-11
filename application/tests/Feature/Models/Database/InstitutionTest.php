<?php

namespace Feature\Models\Database;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $createdModel = Institution::factory()->create();
        $this->assertModelExists($createdModel);

        $retrievedModel = Institution::findOrFail($createdModel->id);
        $this->assertEquals($createdModel->name, $retrievedModel->name);
    }
}
