<?php

namespace App\DataTransferObjects;

use App\Enums\PrivilegeKey;

readonly class InstitutionRoleData
{
    /**
     * @param  PrivilegeKey[]  $privilegeKeys
     */
    public function __construct(
        public string $roleName,
        public string $institutionId,
        public array $privilegeKeys
    ) {
    }
}
