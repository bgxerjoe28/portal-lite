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

    'cbt_disk'       => env('CBT_DISK', 's3_cbt'),
    'presensi_disk'  => env('PRESENSI_DISK', 's3_local'),
    'analytics_disk' => env('ANALYTICS_DISK', 's3_cbt_analytics'),
    'surat_disk'     => env('SURAT_DISK', 's3_local'),

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
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

                // Disk untuk Image CBT (Cloudhost + Cloudflare)
        's3_cbt' => [
            'driver' => 's3',
            'key' => env('CBT_AWS_ACCESS_KEY_ID'),
            'secret' => env('CBT_AWS_SECRET_ACCESS_KEY'),
            'region' => env('CBT_AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('CBT_AWS_BUCKET'),
            'url' => env('CBT_AWS_URL'), 
            'endpoint' => env('CBT_AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('CBT_AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        // Disk untuk file besar/internal (MinIO Lokal)
        's3_local' => [
            'driver' => 's3',
            'key' => env('LOCAL_AWS_ACCESS_KEY_ID'),
            'secret' => env('LOCAL_AWS_SECRET_ACCESS_KEY'),
            'region' => env('LOCAL_AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('LOCAL_AWS_BUCKET'),
            'url' => env('LOCAL_AWS_URL'),
            'endpoint' => env('LOCAL_AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('LOCAL_AWS_USE_PATH_STYLE_ENDPOINT', true), // MinIO WAJIB true
            'http' => ['verify' => false],
            'throw' => false,
        ],

        // Alias 'minio' disk (identik dengan s3_local untuk kemudahan CLI)
        'minio' => [
            'driver' => 's3',
            'key' => env('LOCAL_AWS_ACCESS_KEY_ID'),
            'secret' => env('LOCAL_AWS_SECRET_ACCESS_KEY'),
            'region' => env('LOCAL_AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('LOCAL_AWS_BUCKET'),
            'url' => env('LOCAL_AWS_URL'),
            'endpoint' => env('LOCAL_AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('LOCAL_AWS_USE_PATH_STYLE_ENDPOINT', true),
            'http' => ['verify' => false],
            'throw' => false,
        ],

        // Disk khusus untuk membaca arsip Backup Produksi dari MinIO
        'minio_production' => [
            'driver' => 's3',
            'key' => env('PROD_AWS_ACCESS_KEY_ID', env('LOCAL_AWS_ACCESS_KEY_ID')),
            'secret' => env('PROD_AWS_SECRET_ACCESS_KEY', env('LOCAL_AWS_SECRET_ACCESS_KEY')),
            'region' => env('PROD_AWS_DEFAULT_REGION', env('LOCAL_AWS_DEFAULT_REGION', 'us-east-1')),
            'bucket' => env('PROD_AWS_BUCKET', 'portal-sma-production'),
            'url' => env('PROD_AWS_URL', env('LOCAL_AWS_URL')),
            'endpoint' => env('PROD_AWS_ENDPOINT', env('LOCAL_AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('PROD_AWS_USE_PATH_STYLE_ENDPOINT', true),
            'http' => ['verify' => false],
            'throw' => false,
        ],


        // Disk khusus hasil analisis IRT/CTT — disimpan microservice Python ke MinIO,
        // dibaca Laravel di CbtIrtCallbackController setelah menerima callback.
        's3_cbt_analytics' => [
            'driver'                  => 's3',
            'key'                     => env('LOCAL_AWS_ACCESS_KEY_ID'),
            'secret'                  => env('LOCAL_AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('LOCAL_AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket'                  => env('CBT_ANALYTICS_BUCKET', 'cbt-analytics'),
            'url'                     => env('LOCAL_AWS_URL'),
            'endpoint'                => env('LOCAL_AWS_ENDPOINT'),
            'use_path_style_endpoint' => true, // MinIO WAJIB true
            'http'                    => ['verify' => false],
            'throw'                   => false,
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
