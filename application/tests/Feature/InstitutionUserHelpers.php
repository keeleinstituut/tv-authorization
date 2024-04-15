<?php

namespace Tests\Feature;

use App\Enums\PrivilegeKey;
use App\Models\Department;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Privilege;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Throwable;

trait InstitutionUserHelpers
{
    /**
     * @param $modifyFirstInstitutionUser Closure(InstitutionUser):void|null
     * @param $modifySecondInstitutionUser Closure(InstitutionUser):void|null
     * @param $modifyThirdInstitutionUser Closure(InstitutionUser):void|null
     * @return array{InstitutionUser, InstitutionUser, InstitutionUser}
     *
     * @throws Throwable
     */
    public function createThreeInstitutionUsersInSameInstitution(Closure $modifyFirstInstitutionUser = null,
        Closure $modifySecondInstitutionUser = null,
        Closure $modifyThirdInstitutionUser = null): array
    {
        $institution = Institution::factory()->create();

        $createdInstitutionUsers = Collection::times(3,
            fn () => InstitutionUser::factory()
                ->for($institution)
                ->for(User::factory())
                ->has(Role::factory()->hasAttached(
                    Privilege::firstWhere('key', PrivilegeKey::ViewRole->value)
                ))
                ->create()
        );

        return $createdInstitutionUsers
            ->zip([
                $modifyFirstInstitutionUser ?? function () {
                },
                $modifySecondInstitutionUser ?? function () {
                },
                $modifyThirdInstitutionUser ?? function () {
                },
            ])
            ->mapSpread(function (InstitutionUser $institutionUser, Closure $modify): InstitutionUser {
                $modify($institutionUser);

                return $institutionUser;
            })
            ->each(fn (InstitutionUser $institutionUser) => $institutionUser->saveOrFail())
            ->map(fn (InstitutionUser $institutionUser) => $institutionUser->refresh())
            ->all();
    }

    /**
     * @return array{
     *     institution: Institution,
     *     department: Department,
     *     user: User,
     *     institutionUser: InstitutionUser,
     *     roles: array<Role>
     * }
     *
     * @throws Throwable
     */
    public function createBasicModels(string $email = 'fake@email.ee',
        string $phone = '+372 55555555',
        string $pic = '47103125760',
        string $forename = 'Testo',
        string $surname = 'Testorino',
        bool $attachInstitutionUserToDepartment = true,
        array $privileges = []): array
    {

        $createdInstitution = Institution::factory()->create();
        $createdDepartment = Department::factory()->for($createdInstitution)->create();
        $createdUser = $this->createUser($pic, $forename, $surname);
        $createdRoles = Arr::map(
            $privileges,
            fn ($privilege) => $this->createFactoryRole($privilege, $createdInstitution->id)
        );
        $createdInstitutionUser = $this->createInstitutionUser(
            $createdInstitution,
            $createdUser,
            $email,
            $phone,
            $attachInstitutionUserToDepartment ? $createdDepartment : null,
            ...$createdRoles
        );

        return [
            'institution' => $createdInstitution,
            'department' => $createdDepartment,
            'user' => $createdUser,
            'institutionUser' => $createdInstitutionUser,
            'roles' => $createdRoles,
        ];
    }

    /**
     * @throws Throwable
     */
    public function createInstitutionUser(Institution $institution,
        User $user,
        string $email,
        string $phone = '+372 66666666',
        Department $department = null,
        Role ...$roles): InstitutionUser
    {
        $createdInstitutionUser = InstitutionUser::factory()
            ->for($institution)
            ->for($user)
            ->create([
                'email' => $email,
                'phone' => $phone,
                'department_id' => $department?->id,
            ]);

        $createdInstitutionUser->roles()->sync(
            collect($roles)->map->id->toArray()
        );

        return $createdInstitutionUser->refresh();
    }

    /**
     * @throws Throwable
     */
    public function createUser(string $pic, string $forename, string $surname): User
    {
        return User::factory()->create([
            'personal_identification_code' => $pic,
            'forename' => $forename,
            'surname' => $surname,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function createFactoryRole(PrivilegeKey $privilege, string $institutionId): Role
    {
        $role = Role::factory()->create(['institution_id' => $institutionId]);
        $role->privileges()->sync(Privilege::firstWhere('key', $privilege->value));

        return $role;
    }

    public function createUserInGivenInstitutionWithGivenPrivilege(Institution|Factory $institution, ?PrivilegeKey $privilege): InstitutionUser
    {
        $institutionUserBuilder = InstitutionUser::factory()
            ->for($institution)
            ->for(User::factory());

        if (filled($privilege)) {
            $institutionUserBuilder = $institutionUserBuilder->has(Role::factory()->hasAttached(
                Privilege::firstWhere('key', $privilege->value)
            ));
        }

        return $institutionUserBuilder->create();
    }
}
