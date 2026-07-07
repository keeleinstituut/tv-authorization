<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Claims Allowed Clients
    |--------------------------------------------------------------------------
    |
    | Comma separated list of client IDs used for authenticated requests to
    | JWT Claims endpoints. Used to whitelist clients that need to access user
    | data and permissions.
    |
    */

    'jwt_claims_allowed_clients' => array_filter(explode(',', env('JWT_CLAIMS_ALLOWED_CLIENTS', ""))),
];
