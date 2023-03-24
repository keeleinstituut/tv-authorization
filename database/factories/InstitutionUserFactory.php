<?php

namespace Database\Factories;

use App\Enum\InstitutionUserStatusKey;
use App\Models\Institution;
use App\Models\InstitutionUserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InstitutionUser>
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
            'institution_user_status_id' => fn () => InstitutionUserStatus::where('key', InstitutionUserStatusKey::Created->value)
                    ->first(),
        ];
    }
}
