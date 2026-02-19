# laratusk/supervise

[![CI](https://github.com/laratusk/supervise/actions/workflows/ci.yml/badge.svg)](https://github.com/laratusk/supervise/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)
[![PHP Version](https://img.shields.io/packagist/php-v/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)
[![License](https://img.shields.io/packagist/l/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)

**Supervisor config management for Laravel — define your workers in code, deploy with one command.**

## Introduction

Managing Linux Supervisor configs by hand is error-prone and deployment-hostile. You SSH into servers, edit raw `.conf` files in `/etc/supervisor/conf.d/`, run `supervisorctl reread`, hope nothing breaks — and do it all over again on the next server.

`laratusk/supervise` brings Supervisor config management into your Laravel codebase, the same way Laravel Horizon does for queues. You declare your workers in `config/supervise.php`, commit it to version control, and let the package compile and link the actual `.conf` files. Deployments become a single artisan command.

**Your Supervisor setup becomes part of your codebase.** Reviewed in PRs. Rolled back with git. Consistent across every environment.

```bash
# In your deploy script — that's it
php artisan supervise:compile --reload
```

### How it works

1. You define workers (`horizon`, `queue`, `reverb`) in `config/supervise.php`
2. `supervise:compile` generates `.conf` files into `.supervisor/conf.d/` (gitignored)
3. `supervise:link` symlinks each file individually into `/etc/supervisor/conf.d/`
4. Supervisor picks up the changes with `supervisorctl reread && update` (or pass `--reload`)

### Why not just commit the `.conf` files directly?

Raw Supervisor `.conf` files contain absolute paths (`/var/www/app/artisan`), are tied to a specific server layout, and give you no defaults system — every directive must be repeated for every worker. `laratusk/supervise` handles all of that: paths are resolved at compile time from `base_path()`, sensible defaults are inherited and overridable, and the compiled output is ephemeral (regenerated on each deploy).

| Manual `.conf` files | `laratusk/supervise` |
|---|---|
| Edited directly on the server | Defined in `config/supervise.php` |
| Hardcoded absolute paths | Paths resolved automatically at compile time |
| Copy-paste directives across workers | Shared defaults with per-worker overrides |
| Forgotten after SSH session | Version-controlled, reviewed in PRs |
| Manual `supervisorctl reread` | `supervise:compile --reload` |
| Different per server | Consistent across all environments |

## Requirements

- PHP `^8.2`
- Laravel `^10.0 | ^11.0 | ^12.0`

## Installation

```bash
composer require laratusk/supervise
```

Run the install command once to scaffold directories, publish config, and update `.gitignore`:

```bash
php artisan supervise:install
```

## Configuration

Edit `config/supervise.php` to declare your workers:

```php
return [
    'conf_path'  => env('SUPERVISE_CONF_PATH', '/etc/supervisor/conf.d'),
    'output_path' => '.supervisor/conf.d',

    'defaults' => [
        'numprocs'       => 1,
        'autostart'      => true,
        'autorestart'    => 'unexpected',
        'stopwaitsecs'   => 3600,
        'redirect_stderr' => true,
        'stdout_logfile' => 'AUTO',
        // ... all Supervisor directives available
    ],

    'workers' => [
        'horizon' => [
            'type' => 'horizon',
        ],

        'default-queue' => [
            'type'       => 'queue',
            'connection' => 'redis',
            'queue'      => ['default', 'emails'],
            'numprocs'   => 3,
            'tries'      => 3,
        ],

        // 'reverb' => ['type' => 'reverb'],
    ],

    'groups' => [
        // 'all-workers' => ['horizon', 'default-queue'],
    ],
];
```

### `conf_path`

The system Supervisor `conf.d` directory where symlinks are created by `supervise:link`.

```php
'conf_path' => env('SUPERVISE_CONF_PATH', '/etc/supervisor/conf.d'),
```

### `output_path`

Local path (relative to `base_path()`) where compiled `.conf` files are written. Added to `.gitignore` automatically — the compiled output is ephemeral, not committed.

```php
'output_path' => '.supervisor/conf.d',
```

### `defaults`

Supervisor `[program:x]` directives applied to every worker. Any worker can override individual keys. `null` values are omitted from the compiled output.

```php
'defaults' => [
    // Process control
    'process_name'   => '%(program_name)s_%(process_num)02d',
    'numprocs'       => 1,
    'numprocs_start' => 0,
    'priority'       => 999,
    'autostart'      => true,
    'startsecs'      => 1,
    'startretries'   => 3,
    'autorestart'    => 'unexpected',
    'exitcodes'      => '0',

    // Stopping
    'stopsignal'   => 'TERM',
    'stopwaitsecs' => 3600,
    'stopasgroup'  => true,
    'killasgroup'  => true,

    // User & Environment
    'user'        => 'root',
    'directory'   => null,   // omitted from output when null
    'umask'       => null,
    'environment' => null,   // "KEY=val,KEY2=val2" or null

    // Logging
    'redirect_stderr'         => true,
    'stdout_logfile'          => 'AUTO',
    'stdout_logfile_maxbytes' => '50MB',
    'stdout_logfile_backups'  => 10,
    'stdout_capture_maxbytes' => 0,
    'stdout_events_enabled'   => false,
    'stdout_syslog'           => false,
    'stderr_logfile'          => 'AUTO',
    'stderr_logfile_maxbytes' => '50MB',
    'stderr_logfile_backups'  => 10,
    'stderr_capture_maxbytes' => 0,
    'stderr_events_enabled'   => false,
    'stderr_syslog'           => false,

    // Other
    'serverurl' => null,
],
```

### `workers`

Declare your Supervisor workers. Each entry must have a `type`.

#### `type: 'horizon'`

Runs `php artisan horizon`. Configured as a single process (`process_name=%(program_name)s`).

```php
'horizon' => [
    'type' => 'horizon',
],
```

#### `type: 'queue'`

Runs `php artisan queue:work` with the options you specify.

| Key | Type | Description |
|---|---|---|
| `queue` | `array` | Queue names to process *(required)* |
| `connection` | `string` | Queue connection name |
| `tries` | `int` | Max job attempts |
| `max_time` | `int` | Max seconds the worker should run |
| `sleep` | `int` | Seconds to sleep when the queue is empty |
| `timeout` | `int` | Seconds before a job is forcefully killed |
| `memory` | `int` | Memory limit in MB |
| `backoff` | `int\|string` | Seconds before retrying a failed job |
| `max_jobs` | `int` | Max jobs before the worker restarts |
| `force` | `bool` | Run even in maintenance mode |
| `rest` | `float` | Seconds to rest between jobs |
| `log` | `bool` | Write to `storage/logs/supervisor/{name}.log` |

Any key from `defaults` can also be overridden at the worker level:

```php
'default-queue' => [
    'type'         => 'queue',
    'connection'   => 'redis',
    'queue'        => ['default', 'emails'],
    'numprocs'     => 3,
    'tries'        => 3,
    'stopwaitsecs' => 7200,  // override default
    'user'         => 'deploy',
],
```

#### `type: 'reverb'`

Runs `php artisan reverb:start`. Configured as a single process.

```php
'reverb' => [
    'type' => 'reverb',
],
```

### `groups`

Define Supervisor `[group:x]` sections to manage multiple workers as one unit.

```php
'groups' => [
    'queue-workers' => ['default-queue', 'heavy-queue'],
],
```

## Usage

### First-time setup

```bash
php artisan supervise:install
```

Creates `.supervisor/conf.d/` and `storage/logs/supervisor/`, adds `.supervisor/` to `.gitignore`, and publishes `config/supervise.php`.

### Day-to-day workflow

```bash
# 1. Edit your worker definitions
vim config/supervise.php

# 2. Compile .conf files
php artisan supervise:compile

# 3. Symlink to the system Supervisor directory (once per server)
php artisan supervise:link

# 4. Reload Supervisor
supervisorctl reread && supervisorctl update
```

Steps 2 and 4 can be combined:

```bash
php artisan supervise:compile --reload
```

## Deployment

Add one line to your deploy script and never touch a `.conf` file on the server again:

```bash
php artisan supervise:compile --reload
```

Every deploy regenerates the `.conf` files from your codebase and hot-reloads Supervisor — zero manual steps, zero drift between servers.

**Full deploy script example:**

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Supervisor workers — always up to date
php artisan supervise:compile --reload

sudo systemctl reload php8.3-fpm
```

## Commands Reference

### `supervise:install`

One-time setup. Run this after installing the package.

```bash
php artisan supervise:install
```

Creates `.supervisor/conf.d/`, `storage/logs/supervisor/`, adds `.supervisor/` to `.gitignore`, and publishes the config file.

### `supervise:compile`

Compiles all workers and groups from `config/supervise.php` into `.conf` files. Safe to run repeatedly — existing files are overwritten.

```bash
php artisan supervise:compile
php artisan supervise:compile --reload
```

| Option | Description |
|---|---|
| `--reload` | Run `supervisorctl reread && supervisorctl update` after compiling |

### `supervise:link`

Symlinks each compiled `.conf` file individually into the system `conf_path` directory. Run this once per server after first deploy, or whenever you add a new worker.

```bash
php artisan supervise:link
```

## Supervisor Directives Reference

All standard Supervisor `[program:x]` directives are supported:

| Directive | Default | Description |
|---|---|---|
| `process_name` | `%(program_name)s_%(process_num)02d` | Process name template |
| `numprocs` | `1` | Number of processes to spawn |
| `numprocs_start` | `0` | Starting process number |
| `priority` | `999` | Startup priority |
| `autostart` | `true` | Start automatically with supervisord |
| `startsecs` | `1` | Seconds before a process is considered running |
| `startretries` | `3` | Max startup retries |
| `autorestart` | `unexpected` | Restart strategy (`true`, `false`, `unexpected`) |
| `exitcodes` | `0` | Expected exit codes for `unexpected` restarts |
| `stopsignal` | `TERM` | Signal used to stop the process |
| `stopwaitsecs` | `3600` | Seconds to wait before sending SIGKILL |
| `stopasgroup` | `true` | Send stop signal to the entire process group |
| `killasgroup` | `true` | Send SIGKILL to the entire process group |
| `user` | `root` | Run as this system user |
| `directory` | `null` | Working directory (omitted if null) |
| `umask` | `null` | Process umask |
| `environment` | `null` | Environment variables (`KEY=val,KEY2=val2`) |
| `redirect_stderr` | `true` | Redirect stderr into the stdout log |
| `stdout_logfile` | `AUTO` | stdout log file path |
| `stdout_logfile_maxbytes` | `50MB` | Max stdout log size before rotation |
| `stdout_logfile_backups` | `10` | Number of rotated log files to keep |
| `stdout_capture_maxbytes` | `0` | Max bytes captured for events |
| `stdout_events_enabled` | `false` | Emit events on stdout output |
| `stdout_syslog` | `false` | Write stdout to syslog |
| `stderr_logfile` | `AUTO` | stderr log file path |
| `stderr_logfile_maxbytes` | `50MB` | Max stderr log size before rotation |
| `stderr_logfile_backups` | `10` | Number of rotated stderr log files |
| `stderr_capture_maxbytes` | `0` | Max stderr bytes captured for events |
| `stderr_events_enabled` | `false` | Emit events on stderr output |
| `stderr_syslog` | `false` | Write stderr to syslog |
| `serverurl` | `null` | Supervisor XML-RPC server URL |

## Testing

```bash
composer test
```

With coverage:

```bash
vendor/bin/pest --coverage --min=90
```

## Contributing

Contributions are welcome. Please open an issue first to discuss the change. Before submitting a PR, make sure all checks pass:

```bash
vendor/bin/pint
vendor/bin/phpstan analyse
vendor/bin/rector --dry-run
vendor/bin/pest --coverage --min=90
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for more information.
