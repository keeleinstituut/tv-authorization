<?php

namespace App\Actions;

use App\DataTransferObjects\UserData;
use App\Exceptions\EmptyUserRolesException;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateInstitutionUserAction
{
    /**
     * @throws Throwable
     */
    public function execute(UserData $userData, string $institutionId, array $roleIds): InstitutionUser
    {
        return DB::transaction(function () use ($userData, $institutionId, $roleIds): InstitutionUser {
            $user = User::firstOrCreate(
                ['personal_identification_code' => $userData->pic],
                [
                    'forename' => $userData->forename,
                    'surname' => $userData->surname,
                ]
            );

            $institutionUser = InstitutionUser::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'institution_id' => $institutionId,
                ],
                [
                    'email' => $userData->email,
                    'phone' => $userData->phone,
                ]
            );

            if (empty($roleIds)) {
                throw new EmptyUserRolesException("Couldn't create institution user without roles");
            }

            foreach ($roleIds as $roleId) {
                InstitutionUserRole::firstOrCreate([
                    'institution_user_id' => $institutionUser->id,
                    'role_id' => $roleId,
                ]);
            }

            return $institutionUser;
        });
    }
}
