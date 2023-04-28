<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SSO Internal Client ID
    |--------------------------------------------------------------------------
    |
    | ID of the client used for authenticating requests sent FROM our SSO provider.
    | This value is used for authorizing actions that may only be taken by our SSO.
    |
    */

    'sso_internal_client_id' => env('SSO_INTERNAL_CLIENT_ID'),
];
