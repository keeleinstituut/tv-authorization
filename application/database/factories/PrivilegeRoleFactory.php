<?php

namespace Database\Factories;

use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrivilegeRole>
 *
 * @method static PrivilegeRoleFactory forRole(array $state)
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
            'privilege_id' => $this->faker->unique()->randomElement(Privilege::all()->pluck('id')),
        ];
    }
}
