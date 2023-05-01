<?php

namespace App\Policies;

use App\Enums\PrivilegeKey;
use Illuminate\Support\Facades\Auth;

class UserToImportPolicy
{
    public function manage()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Auth::hasPrivilege(PrivilegeKey::AddUser->value);
    }
}
