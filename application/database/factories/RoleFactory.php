<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 *
 * @method forInstitution(string[] $array)
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'name' => $this->faker->unique()->jobTitle(),
            'institution_id' => Institution::factory(),
        ];
    }
}
