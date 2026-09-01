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
    ],
];
