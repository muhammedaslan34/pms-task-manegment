<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Task Screenshot Disk
    |--------------------------------------------------------------------------
    |
    | Disk used for task screenshot uploads. Set SCREENSHOTS_DISK=s3 in .env to
    | store files on S3 or S3-compatible storage (e.g. iDrive E2).
    |
    */

    'screenshots_disk' => env('SCREENSHOTS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Task Screenshot Public URL
    |--------------------------------------------------------------------------
    |
    | When set (e.g. https://u0s6.fra3.idrivee2-53.com/task-hotelme), image links
    | use direct object URLs. Leave empty to serve via /media/task-images/{id}.
    |
    */

    'screenshots_public_url' => env('SCREENSHOTS_PUBLIC_URL'),

    /*
    |--------------------------------------------------------------------------
    | Task Screenshot Signed URL Lifetime
    |--------------------------------------------------------------------------
    |
    | Minutes a pre-signed bucket URL stays valid. Pages generate fresh URLs on
    | every render, so this only limits how long a copied link keeps working.
    |
    */

    'screenshots_url_ttl' => (int) env('SCREENSHOTS_URL_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
