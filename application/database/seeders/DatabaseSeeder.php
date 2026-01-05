<?php

namespace Database\Seeders;

use App\Enums\PrivilegeKey;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Privilege;
use App\Models\PrivilegeRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $institution = Institution::create([
                'name' => 'Default Institution',
                'short_name' => 'DFI',
            ]);

            $department = Department::create([
                'institution_id' => $institution->id,
                'name' => 'Default Department',
            ]);

            $user = User::create([
                'forename' => 'Admin',
                'surname' => 'User',
                'personal_identification_code' => '12345678901',
            ]);

            $institutionUser = InstitutionUser::create([
                'institution_id' => $institution->id,
                'department_id' => $department->id,
                'user_id' => $user->id,
                'email' => 'admin@example.com',
                'phone' => '+37212345678',
            ]);

            $allPrivileges = Privilege::all();

            if ($allPrivileges->isEmpty()) {
                $allPrivileges = collect(PrivilegeKey::cases())->map(function (PrivilegeKey $privilegeKey) {
                    return Privilege::firstOrCreate([
                        'key' => $privilegeKey,
                    ]);
                });
            }

            $adminRole = Role::create([
                'name' => 'Admin',
                'institution_id' => $institution->id,
                'is_root' => true,
            ]);

            foreach ($allPrivileges as $privilege) {
                PrivilegeRole::create([
                    'role_id' => $adminRole->id,
                    'privilege_id' => $privilege->id,
                ]);
            }

            InstitutionUserRole::create([
                'institution_user_id' => $institutionUser->id,
                'role_id' => $adminRole->id,
            ]);
        });
    }
}
