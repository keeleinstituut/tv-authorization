<?php

namespace Database\Factories;

use App\Enums\PrivilegeKey;
use App\Models\Privilege;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Privilege>
 */
class PrivilegeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $availablePrivileges = collect(PrivilegeKey::cases())->pluck('value');

        return [
            'key' => $this->faker->randomElement($availablePrivileges),
        ];
    }
}
