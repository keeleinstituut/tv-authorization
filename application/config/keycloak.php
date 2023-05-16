<?php

return [
    /*
     * Keycloak Url. The param is used for fetching realm public key.
     * Format: https://your-server.com/auth
     */
    'base_url' => env('KEYCLOAK_BASE_URL', ''),

    /*
     * Keycloak Realm. The param is used for fetching realm public key.
     * Default is master
     */
    'realm' => env('KEYCLOAK_REALM', 'master'),

    /*
     * Keycloak Realm retrieval method. Available options:
     * - cached-api
     * - api
     * - config
     */
    'realm_public_key_retrieval_mode' => env('REALM_PUBLIC_KEY_RETRIEVAL_MODE', 'cached-api'),

    /*
     * Here you may define the cache store that should be used to store
     * realm public key. This can be the name of any store that is
     * configured in app/config/cache.php
     */
    'realm_public_key_cache_store' => env('REALM_PUBLIC_KEY_CACHE_DRIVER', config('redis.default')),

    /*
     * Define static realm public key. To use it, you have to set up
     * KeycloakGuard with ConfigRealmPublicKeyRetriever.
     */
    'realm_public_key' => env('KEYCLOAK_REALM_PUBLIC_KEY', null),

    /*
     * Define the TTL for realm_public_key.
     */
    'realm_public_key_cache_ttl' => env('REALM_PUBLIC_KEY_CACHE_TTL', 86400),

    /*
     * Define the custom claims attribute of the JWT token where all user info is placed.
     */
    'jwt_payload_custom_claims_attribute' => env('KEYCLOAK_JWT_PAYLOAD_CUSTOM_CLAIMS_KEY', 'tolkevarav'),

    /*
     * The param is needed in case if the user data will be loaded from the database.
     * The field from `users` table that contains the user unique identifier (eg. username, email, nickname).
     * This will be confronted against `token_principal_attribute` attribute, while authenticating.
     */
    'user_provider_credential' => env('KEYCLOAK_USER_PROVIDER_CREDENTIAL', 'personal_identification_code'),

    /*
     * The param is needed in case if the user data will be loaded from the database.
     * The property from JWT token that contains the user identifier.
     * This will be confronted against `user_provider_credential` attribute, while authenticating.
     */
    'token_principal_attribute' => env('KEYCLOAK_TOKEN_PRINCIPAL_ATTRIBUTE', 'tolkevarav.personalIdentificationCode'),

    /*
     * You can add a leeway to account for when there is a clock skew times between the signing and verifying servers.
     * If you are facing issues like *"Cannot handle token prior to <DATE>"* try to set it `60` (seconds).
     */
    'leeway' => env('KEYCLOAK_LEEWAY', 0),

    /*
     * By default this package **always** will look at first for a `Bearer` token.
     * Additionally, if this option is enabled, then it will try to get a token from this custom request param.
     */
    'input_key' => env('KEYCLOAK_TOKEN_INPUT_KEY', null),
];
