<?php

namespace App\Actions;

use App\DataTransferObjects\UserData;
use App\Enum\InstitutionUserStatus;
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
    public function execute(UserData $userData, string $institutionId, string $roleId): InstitutionUser
    {
        return DB::transaction(function () use ($userData, $institutionId, $roleId): InstitutionUser {
            $user = User::firstOrCreate(
                ['personal_identification_code' => $userData->pin],
                [
                    'forename' => $userData->forename,
                    'surname' => $userData->surname,
                    'email' => $userData->email,
                ]
            );

            $institutionUser = InstitutionUser::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'institution_id' => $institutionId,
                ],
                [
                    'status' => InstitutionUserStatus::Activated,
                ]
            );

            InstitutionUserRole::firstOrCreate([
                'institution_user_id' => $institutionUser->id,
                'role_id' => $roleId,
            ]);

            return $institutionUser;
        });
    }
}
