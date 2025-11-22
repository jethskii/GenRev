<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Laravel needs a default disk. For normal applications, "public" is
    | recommended because we want uploaded files to be viewable on the
    | website (e.g., product images).
    |
    */

    'default' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Disks define where Laravel will store different files. You can set up
    | multiple disks, each with its own purpose.
    |
    */

    'disks' => [

        // PRIVATE storage (files NOT publicly accessible)
        'local' => [
            'driver'     => 'local',
            'root'       => storage_path('app/private'),
            'visibility' => 'private',
            'throw'      => false,
        ],

        // PUBLIC storage (files visible at: APP_URL/storage/xxxx)
        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        // Amazon S3 example — safe to keep unchanged
        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | When running `php artisan storage:link`, Laravel will link:
    |   public/storage → storage/app/public
    |
    | This allows images stored on the "public" disk to be accessible.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
