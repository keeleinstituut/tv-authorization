<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    use WithFaker;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'forename' => $this->faker->firstName(),
            'surname' => $this->faker->lastName(),
            'personal_identification_code' => $this->faker->unique()->estonianPIC(),
        ];
    }
}
