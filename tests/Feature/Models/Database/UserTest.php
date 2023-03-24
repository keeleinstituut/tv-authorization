<?php

namespace Feature\Models\Database;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $createdModel = User::factory()->create();
        $this->assertModelExists($createdModel);

        $retrievedModel = User::findOrFail($createdModel->id);
        $this->assertEquals(
            $createdModel->personal_identification_code,
            $retrievedModel->personal_identification_code
        );
    }

    public function test_duplicate_pic_fails(): void
    {
        $this->expectException(QueryException::class);

        User::factory()
            ->count(2)
            ->create(['personal_identification_code' => '39611300828']);
    }
}
