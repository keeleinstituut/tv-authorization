<?php

namespace Feature\Models\Database;

use App\Enum\InstitutionUserStatusKey;
use App\Models\InstitutionUserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class InstitutionUserStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_user_status_table_is_equal_to_enum(): void
    {
        $keysInDatabase = InstitutionUserStatus::all()
            ->map(fn ($model) => $model->key->value)
            ->toArray();

        $keysInEnum = Arr::map(
            InstitutionUserStatusKey::cases(),
            fn ($enum) => $enum->value
        );

        $this->assertEmpty(array_diff($keysInDatabase, $keysInEnum)); // Check no keys missing in enum
        $this->assertEmpty(array_diff($keysInEnum, $keysInDatabase)); // Check no keys missing in database
    }
}
