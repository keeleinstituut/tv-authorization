<?php

namespace App\Http;

readonly class RouteConstants
{
    public const INSTITUTION_USERS_INDEX = '/institution-users';

    public const INSTITUTION_USER_ID = 'institution_user_id';

    public const ROLES_INDEX = '/roles';

    public const ROLE_ID = 'role_id';

    public const ROLE_SUBPATH = '/{'.self::ROLE_ID.'}';

    public const INSTITUTION_USER_SUBPATH = '/{'.self::INSTITUTION_USER_ID.'}';
}
