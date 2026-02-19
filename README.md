# laratusk/supervise

[![CI](https://github.com/laratusk/supervise/actions/workflows/ci.yml/badge.svg)](https://github.com/laratusk/supervise/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)
[![PHP Version](https://img.shields.io/packagist/php-v/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)
[![License](https://img.shields.io/packagist/l/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)

Manage Linux Supervisor configuration files using Laravel config + Blade templates.

## Introduction

`laratusk/supervise` takes the pain out of managing Supervisor process configurations for your Laravel application. Instead of manually editing `.conf` files scattered across your server, you define everything in a single PHP config file — and the package compiles it into proper Supervisor `.conf` files and symlinks them to the system directory.

Think of it like **Laravel Horizon's config approach**: define your workers, queues, and options in `config/supervise.php`, then run one command to compile and reload.

**Compared to managing Supervisor configs manually:**

| Manual approach | With supervise |
|---|---|
| Edit `/etc/supervisor/conf.d/*.conf` directly | Edit `config/supervise.php` |
| Remember all Supervisor directive names | Use Laravel config with sensible defaults |
| Manually run `supervisorctl reread && update` | Run `supervise:compile --reload` |
| No version control | Config lives in your repo |

## Requirements

- PHP `^8.2`
- Laravel `^10.0 \| ^11.0 \| ^12.0`

## Installation

```bash
composer require laratusk/supervise
```

Run the install command to set up directories, publish config, and update `.gitignore`:

```bash
php artisan supervise:install
```

## Configuration

After installation, edit `config/supervise.php`:

### `conf_path`

The system Supervisor `conf.d` directory path. Symlinks created by `supervise:link` will be placed here.

```php
'conf_path' => env('SUPERVISE_CONF_PATH', '/etc/supervisor/conf.d'),
```

### `output_path`

Local output directory for compiled `.conf` files, relative to `base_path()`. This directory is added to `.gitignore` automatically.

```php
'output_path' => '.supervisor/conf.d',
```

### `defaults`

Default Supervisor `[program:x]` directive values applied to **all** workers. Any worker can override these.

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
    'stopsignal'     => 'TERM',
    'stopwaitsecs'   => 3600,
    'stopasgroup'    => true,
    'killasgroup'    => true,

    // User & Environment
    'user'           => 'root',
    'directory'      => null,     // null = omitted from output
    'umask'          => null,
    'environment'    => null,     // "KEY=val,KEY2=val2" or null

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

> **Note:** Any value set to `null` is omitted from the compiled `.conf` file. This lets you define optional directives only when needed.

### `workers`

Define your Supervisor workers. Each worker must have a `type` key.

#### `type: 'horizon'`

Runs `php artisan horizon`. Single-process (`process_name=%(program_name)s`).

```php
'horizon' => [
    'type' => 'horizon',
],
```

#### `type: 'queue'`

Runs `php artisan queue:work` with configurable options.

| Key | Type | Default | Description |
|---|---|---|---|
| `queue` | `array` | *(required)* | Queue names to process |
| `connection` | `string` | `null` | Queue connection (omitted if null) |
| `tries` | `int` | — | Max job attempts |
| `max_time` | `int` | — | Max seconds worker should run |
| `sleep` | `int` | — | Seconds to sleep when no jobs |
| `timeout` | `int` | — | Seconds before a job is forcefully killed |
| `memory` | `int` | — | Memory limit in MB |
| `backoff` | `int\|string` | — | Seconds to wait before retrying failed jobs |
| `max_jobs` | `int` | — | Max jobs before the worker stops |
| `force` | `bool` | — | Run even in maintenance mode |
| `rest` | `float` | — | Seconds to rest between jobs |
| `log` | `bool` | — | Write to `storage/logs/supervisor/{name}.log` |

```php
'default-queue' => [
    'type'       => 'queue',
    'connection' => 'redis',
    'queue'      => ['default', 'emails'],
    'numprocs'   => 3,
    'tries'      => 3,
],
```

Any worker can also override any key from `defaults`:

```php
'heavy-queue' => [
    'type'         => 'queue',
    'queue'        => ['exports'],
    'numprocs'     => 1,
    'stopwaitsecs' => 7200,  // override default
    'user'         => 'deploy',
],
```

#### `type: 'reverb'`

Runs `php artisan reverb:start`. Single-process.

```php
'reverb' => [
    'type' => 'reverb',
],
```

### `groups`

Define Supervisor `[group:x]` sections. Keys are group names, values are arrays of worker names.

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

This creates `.supervisor/conf.d/`, `storage/logs/supervisor/`, adds `.supervisor/` to `.gitignore`, and publishes the config and view files.

### Workflow

1. **Edit** `config/supervise.php` to define your workers
2. **Compile** to generate `.conf` files:
   ```bash
   php artisan supervise:compile
   ```
3. **Link** the compiled files to Supervisor's conf directory:
   ```bash
   php artisan supervise:link
   ```
4. **Reload** Supervisor to pick up the changes:
   ```bash
   supervisorctl reread && supervisorctl update
   ```

Or combine steps 2 and 4:

```bash
php artisan supervise:compile --reload
```

## Commands Reference

### `supervise:install`

First-time setup. Creates directories, updates `.gitignore`, publishes config and views.

```bash
php artisan supervise:install
```

### `supervise:compile`

Compiles all workers and groups into `.conf` files in `output_path`. Idempotent — safe to run multiple times.

```bash
# Compile only
php artisan supervise:compile

# Compile and reload Supervisor
php artisan supervise:compile --reload
```

**Options:**

| Option | Description |
|---|---|
| `--reload` | Run `supervisorctl reread && supervisorctl update` after compiling |

### `supervise:link`

Creates individual symlinks from the `conf_path` system directory to each compiled `.conf` file. Requires `supervise:compile` to be run first.

```bash
php artisan supervise:link
```

## Deployment

In your deploy script, simply run:

```bash
php artisan supervise:compile --reload
```

This compiles the latest config and instructs Supervisor to pick up the changes — no manual file editing required.

Full example with zero-downtime deployment:

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Compile and reload Supervisor workers
php artisan supervise:compile --reload

# Reload PHP-FPM
sudo systemctl reload php8.3-fpm
```

## Supervisor Directives Reference

All standard Supervisor `[program:x]` directives are supported through the `defaults` config and worker-level overrides:

| Directive | Default | Description |
|---|---|---|
| `process_name` | `%(program_name)s_%(process_num)02d` | Process name template |
| `numprocs` | `1` | Number of processes |
| `numprocs_start` | `0` | Starting process number |
| `priority` | `999` | Startup priority |
| `autostart` | `true` | Start on supervisord startup |
| `startsecs` | `1` | Seconds to consider process running |
| `startretries` | `3` | Max start retries |
| `autorestart` | `unexpected` | Auto-restart strategy |
| `exitcodes` | `0` | Expected exit codes |
| `stopsignal` | `TERM` | Signal to stop process |
| `stopwaitsecs` | `3600` | Seconds to wait before SIGKILL |
| `stopasgroup` | `true` | Send stop signal to process group |
| `killasgroup` | `true` | Send SIGKILL to process group |
| `user` | `root` | Run process as this user |
| `directory` | `null` | Working directory |
| `umask` | `null` | Process umask |
| `environment` | `null` | Environment variables |
| `redirect_stderr` | `true` | Redirect stderr to stdout log |
| `stdout_logfile` | `AUTO` | stdout log file path |
| `stdout_logfile_maxbytes` | `50MB` | Max log file size |
| `stdout_logfile_backups` | `10` | Number of log file backups |
| `stdout_capture_maxbytes` | `0` | Max bytes to capture |
| `stdout_events_enabled` | `false` | Enable stdout events |
| `stdout_syslog` | `false` | Write stdout to syslog |
| `stderr_logfile` | `AUTO` | stderr log file path |
| `stderr_logfile_maxbytes` | `50MB` | Max stderr log file size |
| `stderr_logfile_backups` | `10` | Number of stderr log backups |
| `stderr_capture_maxbytes` | `0` | Max stderr bytes to capture |
| `stderr_events_enabled` | `false` | Enable stderr events |
| `stderr_syslog` | `false` | Write stderr to syslog |
| `serverurl` | `null` | Supervisor XML-RPC server URL |

## Testing

```bash
composer test
```

Or with coverage:

```bash
vendor/bin/pest --coverage --min=90
```

## Contributing

Contributions are welcome! Please open an issue first to discuss what you'd like to change. Ensure all tests pass and code quality tools are satisfied before submitting a PR:

```bash
vendor/bin/pint
vendor/bin/phpstan analyse
vendor/bin/rector --dry-run
vendor/bin/pest --coverage --min=90
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for more information.
