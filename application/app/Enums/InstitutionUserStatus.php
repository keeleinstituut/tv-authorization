<?php

namespace App\Enums;

enum InstitutionUserStatus: string
{
    case Active = 'ACTIVE';
    case Deactivated = 'DEACTIVATED';
    case Archived = 'ARCHIVED';
}
