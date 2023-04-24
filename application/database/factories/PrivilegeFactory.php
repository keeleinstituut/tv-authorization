<?php

namespace Database\Factories;

use App\Enum\PrivilegeKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Privilege>
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
            'key' => fake()->randomElement($availablePrivileges),
        ];
    }
}
