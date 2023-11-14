<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AMQP connection properties
    |--------------------------------------------------------------------------
    */
    'connection' => [
        'host' => env('AMQP_HOST', 'localhost'),
        'port' => env('AMQP_PORT', 5672),
        'username' => env('AMQP_USER', 'guest'),
        'password' => env('AMQP_PASSWORD', 'guest'),
        'vhost' => env('AMQP_VHOST', '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AMQP publisher properties (remove if not needed)
    |--------------------------------------------------------------------------
    */
    'publisher' => [
        'exchanges' => [
            [
                'exchange' => 'institution',
                'type' => 'fanout',
            ],
            [
                'exchange' => 'institution-user',
                'type' => 'fanout',
            ],
            [
                'exchange' => env('AUDIT_LOG_EVENTS_EXCHANGE'),
                'type' => 'topic',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log AMQP properties (remove if not needed)
    |--------------------------------------------------------------------------
    */
    'audit_logs' => [
        'exchange' => env('AUDIT_LOG_EVENTS_EXCHANGE'),
        'trace_id_http_header' => env('AUDIT_LOG_TRACE_ID_HTTP_HEADER'),
    ],
];
