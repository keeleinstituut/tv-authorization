<?php

namespace Database\Factories;

use App\Enums\PrivilegeKey;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrivilegeRole>
 */
class PrivilegeRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'privilege_id' => fake()->unique()->randomElement(Privilege::all()->pluck('id')),
        ];
    }
}
