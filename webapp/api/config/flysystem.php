<?php

return [
    'default' => 'local',

    'connections' => [
        'local' => [
            'driver' => 'local',
            'path' => storage_path('files'),
            // 'visibility' => 'public',
            // 'pirate'     => false,
            // 'eventable'  => true,
            // 'cache'      => 'foo'
        ],

        'null' => [
            'driver' => 'null',
            // 'eventable' => true,
            // 'cache'     => 'foo'
        ],
        'zip' => [
            'driver' => 'zip',
            'path' => storage_path('files.zip'),
            // 'visibility' => 'public',
            // 'pirate'     => false,
            // 'eventable'  => true,
            // 'cache'      => 'foo'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Flysystem Cache
    |--------------------------------------------------------------------------
    |
    | Here are each of the cache configurations setup for your application.
    | There are currently two drivers: illuminate and adapter. Examples of
    | configuration are included. You can of course have multiple connections
    | per driver as shown.
    |
    */

    'cache' => [
        'adapter' => [
            'driver' => 'adapter',
            'adapter' => 'local', // as defined in connections
            'file' => 'flysystem.json',
            'ttl' => 600,
        ],
    ],
];
