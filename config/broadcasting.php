<?php

return [
    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [

        'pusher' => [
            'driver'  => 'pusher',
            'key'     => env('PUSHER_APP_KEY'),
            'secret'  => env('PUSHER_APP_SECRET'),
            'app_id'  => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
                'useTLS'  => filter_var(env('PUSHER_APP_USETLS', true), FILTER_VALIDATE_BOOLEAN),
                // KHÔNG set host/port nếu dùng Pusher cloud
            ],
            'client_options' => [],
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];
