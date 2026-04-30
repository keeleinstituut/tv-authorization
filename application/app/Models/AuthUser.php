<?php
namespace App\Models;

use App\Enums\InstitutionType;
use App\Enums\PrivilegeKey;
use KeycloakAuthGuard\Models\JwtPayloadUser;

class AuthUser extends JwtPayloadUser
{
    private Institution|null $institution = null;

    public function institution(): Institution|null
    {
        if (is_null($this->institution)) {
            $this->institution = Institution::query()
                ->where('institution_id', $this->institutionId)
                ->first();
        }

        return $this->institution;
    }

    public function belongsToTranslationAgency(): bool
    {
        return $this->institution()?->type === InstitutionType::TranslationAgency;
    }

    public function hasPrivilege(PrivilegeKey | string $privilege): bool
    {
        if (empty($this->privileges)) {
            return false;
        }

        $privilegeKey = is_string($privilege) ? PrivilegeKey::tryFrom($privilege) : $privilege;
        if (empty($privilegeKey)) {
            return false;
        }

        return in_array($privilegeKey->value, $this->privileges);
    }

    /**
     * @param  array<PrivilegeKey|string>  $privileges
     */
    public function hasAtLeastOnePrivilege(array $privileges): bool
    {
        return array_any($privileges, fn($privilege) => $this->hasPrivilege($privilege));

    }
}
