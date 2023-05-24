<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @extends Factory<InstitutionUser>
 */
class InstitutionUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'user_id' => User::factory(),
            'email' => $this->faker->email,
            'phone' => $this->generateRandomEstonianPhoneNumber(),
        ];
    }

    private function generateRandomEstonianPhoneNumber(): string
    {
        return Str::of('+372')
            ->append(fake()->randomElement([' ', '']))
            ->append(fake()->randomElement(['3', '4', '5', '6', '7']))
            ->append(
                Collection::times(
                    fake()->numberBetween(6, 7),
                    fake()->randomDigit(...)
                )->join('')
            )->toString();
    }
}
