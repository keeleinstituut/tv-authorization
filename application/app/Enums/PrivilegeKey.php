<?php

namespace App\Enums;

enum PrivilegeKey: string
{
    case AddRole = 'ADD_ROLE';
    case ViewRole = 'VIEW_ROLE';
    case EditRole = 'EDIT_ROLE';
    case DeleteRole = 'DELETE_ROLE';
    case AddUser = 'ADD_USER';
    case EditUser = 'EDIT_USER';
    case ViewUser = 'VIEW_USER';
    case ExportUser = 'EXPORT_USER';
    case ActivateUser = 'ACTIVATE_USER';
    case DeactivateUser = 'DEACTIVATE_USER';
    case ArchiveUser = 'ARCHIVE_USER';
    case SetUserWorkTime = 'SET_USER_WORKTIME';
    case SetUserVacation = 'SET_USER_VACATION';
}
