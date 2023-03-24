<?php

namespace App\Enum;

enum InstitutionUserStatusKey: string
{
    case Created = 'CREATED';
    case Activated = 'ACTIVATED';
    case Deactivated = 'DEACTIVATED';
}
