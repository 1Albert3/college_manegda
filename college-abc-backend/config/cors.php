<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration CORS pour permettre la communication frontend-backend
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:4200',
        'http://localhost:1200',
        'http://localhost:3000',
        'http://127.0.0.1:4200',
        'http://127.0.0.1:1200',
        'http://127.0.0.1:3000',
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 60 * 60 * 24, // 24 hours

    'supports_credentials' => true,

];
