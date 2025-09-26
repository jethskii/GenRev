<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        // Default guard / password broker can be overridden via .env
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | - "web" uses session, fits blade-based login (your AuthController).
    | - "api" uses token driver by default; swap to sanctum/jwt as needed.
    */
    'guards' => [
        'web' => [
            'driver'   => env('AUTH_WEB_DRIVER', 'session'),
            'provider' => env('AUTH_PROVIDER', 'users'),
        ],

        'api' => [
            'driver'   => env('AUTH_API_DRIVER', 'token'),  // or 'sanctum'
            'provider' => env('AUTH_PROVIDER', 'users'),
            // 'hash'   => false, // enable if using plain token driver hashing
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Eloquent provider is default. You can switch the model in .env:
    |   AUTH_MODEL="App\Models\User"
    | Or change to database provider by uncommenting below.
    */
    'providers' => [
        'users' => [
            'driver' => env('AUTH_USERS_DRIVER', 'eloquent'),
            'model'  => env('AUTH_MODEL', App\Models\User::class),
            // If you prefer database provider instead of eloquent:
            // 'driver' => 'database',
            // 'table'  => env('AUTH_TABLE', 'users'),
        ],

        // Optional: a separate provider for employees if ever needed
        // 'employees' => [
        //     'driver' => 'eloquent',
        //     'model'  => App\Models\Employee::class,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Table name aligns with Laravel 10/11 default: password_reset_tokens
    | You can change the expire/throttle windows via .env.
    */
    'passwords' => [
        'users' => [
            'provider' => env('AUTH_PROVIDER', 'users'),
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => (int) env('AUTH_PASSWORD_EXPIRE', 60),   // minutes
            'throttle' => (int) env('AUTH_PASSWORD_THROTTLE', 60), // minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Number of seconds before a password confirmation times out.
    | Default: 3 hours (10800).
    */
    'password_timeout' => (int) env('AUTH_PASSWORD_TIMEOUT', 10800),
];
