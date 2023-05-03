<?php

namespace App\Policies;

use App\Enums\PrivilegeKey;
use Illuminate\Support\Facades\Auth;

class InstitutionUserPolicy
{
    public function import()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Auth::hasPrivilege(PrivilegeKey::AddUser->value);
    }

    public function viewList()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Auth::hasPrivilege(PrivilegeKey::AddUser->value);
    }
}
