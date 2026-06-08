<?php

namespace App\Actions;

use App\DataTransferObjects\InstitutionData;
use App\DataTransferObjects\InstitutionRoleData;
use App\DataTransferObjects\UserData;
use App\Enums\DefaultRole;
use Throwable;

readonly class CreateInstitutionWithMainUserAction
{
    public function __construct(
        private CreateInstitutionAction $createInstitutionAction,
        private CreateInstitutionRoleAction $createRoleAction,
        private CreateInstitutionUserAction $createInstitutionUserAction,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(InstitutionData $institutionData, UserData $userData): void
    {
        $institution = $this->createInstitutionAction->execute($institutionData);
        $defaultRole = $institutionData->belongsToTranslationAgency() ? DefaultRole::TranslationAgencyAdmin : DefaultRole::InstitutionAdmin;
        $role = $this->createRoleAction->execute(
            new InstitutionRoleData(
                $defaultRole->value,
                $institution->id,
                DefaultRole::privileges($defaultRole)
            )
        );

        $this->createInstitutionUserAction->execute(
            $userData,
            $institution->id,
            [$role->id]
        );
    }
}
