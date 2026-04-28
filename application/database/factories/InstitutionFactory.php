<?php

namespace Database\Factories;

use App\Enums\InstitutionType;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'short_name' => null,
            'phone' => null,
            'email' => $this->faker->companyEmail(),
            'institution_type' => InstitutionType::Institution,
            //'logo_url' => $this->faker->url(),
        ];
    }
}
