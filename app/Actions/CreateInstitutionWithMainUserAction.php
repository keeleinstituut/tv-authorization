<?php

namespace App\Actions;

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
    public function execute(string $institutionName, UserData $userData): void
    {
        $institution = $this->createInstitutionAction->execute($institutionName);

        $role = $this->createRoleAction->execute(
            new InstitutionRoleData(
                DefaultRole::InstitutionAdmin->value,
                $institution->id,
                DefaultRole::privileges(DefaultRole::InstitutionAdmin)
            )
        );

        $this->createInstitutionUserAction->execute(
            $userData,
            $institution->id,
            $role->id
        );
    }
}
