<?php

namespace Database\Factories;

use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionUserRole>
 */
class InstitutionUserRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_user_id' => InstitutionUser::factory(),
            'role_id' => Role::factory(),
        ];
    }
}
