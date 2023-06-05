<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'institution_id' => Institution::factory(),
        ];
    }
}
