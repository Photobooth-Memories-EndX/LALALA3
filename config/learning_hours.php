<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Learning Hours Configuration
    |--------------------------------------------------------------------------
    |
    | Defines step increment (0.5), bucket interval in seconds (1800),
    | minimum value, batch chunk size, and dedicated log channel for audit.
    |
    */

    'step'           => 0.5,
    'bucket_seconds' => 1800,
    'minimum'        => 0.0,
    'chunk_size'     => 200,
    'log_channel'    => env('LEARNING_HOURS_LOG_CHANNEL', 'single'),

    'playlist_content_user' => [
        'chunk_size' => (int) env('LEARNING_HOURS_PLAYLIST_CHUNK_SIZE', 500),
        'schedule'   => [
            'enabled'  => filter_var(env('LEARNING_HOUR_PLAYLIST_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'time'     => env('LEARNING_HOUR_PLAYLIST_TIME', '00:00'),
            'timezone' => env('LEARNING_HOUR_PLAYLIST_TIMEZONE', 'Asia/Jakarta'),
        ],
    ],
];
