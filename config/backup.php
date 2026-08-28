<?php

return [

    'backup' => [
        'name' => env('APP_NAME', 'Portal_Lite'),

        'source' => [
            'files' => [
                'include' => [
                    base_path(), // Mencakup kode program dan storage foto
                ],

                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('.git'),
                    public_path('build'),
                    public_path('hot'),
                    storage_path('app/backup-temp'),
                    storage_path('framework/cache'),
                    storage_path('framework/sessions'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => null,
            ],

            'databases' => [
                // Pastikan di .env DB_CONNECTION=pgsql
                env('DB_CONNECTION', 'pgsql'),
            ],
        ],

        // Gunakan Gzip agar file .sql Postgres lebih kecil
        'database_dump_compressor' => Spatie\DbDumper\Compressors\GzipCompressor::class,

        'database_dump_file_timestamp_format' => 'Y-m-d-H-i-s',
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => 'sql',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9, // Kompresi maksimal untuk hemat storage
            'filename_prefix' => 'backup_portal_lite_',
            'disks' => array_values(array_filter(array_map('trim', explode(',', env('BACKUP_DISKS', 'local'))))),
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',
        'tries' => 2,
        'retry_delay' => 60,
    ],

    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('ADMIN_EMAIL', 'admin@example.com'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'name' => env('APP_NAME'),
            ],
        ],
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'Portal_Lite'),
            'disks' => array_values(array_filter(array_map('trim', explode(',', env('BACKUP_DISKS', 'local'))))),
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                // Kita batasi monitor di 10GB (sesuai hitungan storage kita kemarin)
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 10000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 14,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 6,
            'keep_yearly_backups_for_years' => 1,
            'delete_oldest_backups_when_using_more_megabytes_than' => 15000, // Hapus jika sudah tembus 15GB
        ],
    ],
];
