<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supervisor conf.d System Path
    |--------------------------------------------------------------------------
    |
    | The path to the system Supervisor conf.d directory where symlinks will
    | be created by supervise:link. This is typically /etc/supervisor/conf.d.
    |
    */
    'conf_path' => env('SUPERVISE_CONF_PATH', '/etc/supervisor/conf.d'),

    /*
    |--------------------------------------------------------------------------
    | Local Compiled Output Path
    |--------------------------------------------------------------------------
    |
    | Path relative to base_path() where compiled .conf files will be stored.
    | These files are committed to .gitignore and managed by supervise:compile.
    |
    */
    'output_path' => '.supervisor/conf.d',

    /*
    |--------------------------------------------------------------------------
    | Supervisor Program Defaults
    |--------------------------------------------------------------------------
    |
    | Default Supervisor [program:x] directive values applied to ALL workers.
    | Individual workers can override any of these values. Null values are
    | omitted from the generated .conf files.
    |
    */
    'defaults' => [

        // Process control
        'process_name' => '%(program_name)s_%(process_num)02d',
        'numprocs' => 1,
        'numprocs_start' => 0,
        'priority' => 999,
        'autostart' => true,
        'startsecs' => 1,
        'startretries' => 3,
        'autorestart' => 'unexpected',
        'exitcodes' => '0',

        // Stopping
        'stopsignal' => 'TERM',
        'stopwaitsecs' => 3600,
        'stopasgroup' => true,
        'killasgroup' => true,

        // User & Environment
        'user' => 'root',
        'directory' => null,      // null = not rendered in output
        'umask' => null,
        'environment' => null,      // string "KEY=val,KEY2=val2" or null

        // Logging
        'redirect_stderr' => true,
        'stdout_logfile' => 'AUTO',
        'stdout_logfile_maxbytes' => '50MB',
        'stdout_logfile_backups' => 10,
        'stdout_capture_maxbytes' => 0,
        'stdout_events_enabled' => false,
        'stdout_syslog' => false,
        'stderr_logfile' => 'AUTO',
        'stderr_logfile_maxbytes' => '50MB',
        'stderr_logfile_backups' => 10,
        'stderr_capture_maxbytes' => 0,
        'stderr_events_enabled' => false,
        'stderr_syslog' => false,

        // Other
        'serverurl' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Workers
    |--------------------------------------------------------------------------
    |
    | Define your Supervisor workers here. Each worker must have a 'command' key
    | (the exact command line to run). Worker name is the array key.
    | You can override any key from 'defaults' above per worker.
    |
    | Optional: set 'log' => true to use storage/logs/supervisor/{name}.log
    | for stdout_logfile.
    |
    */
    'workers' => [

        'horizon' => [
            'command' => 'php artisan horizon',
        ],

        'default-queue' => [
            'command' => 'php artisan queue:work redis --queue=default --tries=3',
            'numprocs' => 3,
        ],

        // Example: Laravel Reverb WebSocket server
        // 'reverb' => [
        //     'command' => 'php artisan reverb:start',
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    |
    | Define Supervisor [group:x] sections. Each key is the group name and the
    | value is an array of worker names defined in the 'workers' section above.
    |
    */
    'groups' => [
        // 'queue-workers' => ['default-queue'],
    ],

];
