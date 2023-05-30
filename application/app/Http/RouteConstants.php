<?php

namespace App\Http;

readonly class RouteConstants
{
    public const INSTITUTION_USERS_INDEX = '/institution-users';

    public const INSTITUTION_USER_ID = 'institution_user_id';

    public const DEPARTMENTS_INDEX = '/departments';

    public const DEPARTMENT_ID = 'department_id';

    public const DEPARTMENT_SUBPATH = '/{'.self::DEPARTMENT_ID.'}';

    public const ROLES_INDEX = '/roles';

    public const ROLE_ID = 'role_id';

    public const ROLE_SUBPATH = '/{'.self::ROLE_ID.'}';

    public const INSTITUTION_USER_SUBPATH = '/{'.self::INSTITUTION_USER_ID.'}';
}
