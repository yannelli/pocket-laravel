<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pocket API Key
    |--------------------------------------------------------------------------
    |
    | Your Pocket API key for authenticating requests. You can find this in
    | your Pocket dashboard under Settings > API Keys.
    |
    */
    'api_key' => env('POCKET_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Pocket API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Pocket API. You should not need to change this
    | unless you are using a custom or staging environment.
    |
    */
    'base_url' => env('POCKET_BASE_URL', 'https://public.heypocketai.com'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The API version to use. Currently only v1 is supported.
    |
    */
    'api_version' => env('POCKET_API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests.
    |
    */
    'timeout' => env('POCKET_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic request retries on failure.
    |
    */
    'retry' => [
        'times' => env('POCKET_RETRY_TIMES', 3),
        'sleep' => env('POCKET_RETRY_SLEEP', 1000), // milliseconds
    ],
];
