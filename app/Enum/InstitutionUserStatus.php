<?php

namespace App\Enum;

enum InstitutionUserStatus: string
{
    case Created = 'CREATED';
    case Activated = 'ACTIVATED';
    case Deactivated = 'DEACTIVATED';
}
