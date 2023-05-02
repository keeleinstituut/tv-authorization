<?php

namespace App\Policies;

use App\Enums\PrivilegeKey;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    public function import()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Auth::hasPrivilege(PrivilegeKey::AddUser->value);
    }
}
