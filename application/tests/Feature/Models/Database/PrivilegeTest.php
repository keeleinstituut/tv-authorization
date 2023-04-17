<?php

namespace Feature\Models\Database;

use App\Enums\PrivilegeKey;
use App\Models\Privilege;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class PrivilegeTest extends TestCase
{
    use RefreshDatabase;

    public function test_privilege_table_is_equal_to_enum(): void
    {
        $keysInDatabase = Privilege::all()
            ->map(fn ($model) => $model->key->value)
            ->toArray();

        $keysInEnum = Arr::map(
            PrivilegeKey::cases(),
            fn ($enum) => $enum->value
        );

        $this->assertEmpty(array_diff($keysInDatabase, $keysInEnum)); // Check no keys missing in enum
        $this->assertEmpty(array_diff($keysInEnum, $keysInDatabase)); // Check no keys missing in database
    }
}
