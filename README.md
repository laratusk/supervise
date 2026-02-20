# laratusk/supervise

[![CI](https://github.com/laratusk/supervise/actions/workflows/ci.yml/badge.svg)](https://github.com/laratusk/supervise/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)
[![PHP Version](https://img.shields.io/packagist/php-v/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)
[![License](https://img.shields.io/packagist/l/laratusk/supervise.svg)](https://packagist.org/packages/laratusk/supervise)

Define your [Supervisor](http://supervisord.org/) workers in Laravel config and generate real `.conf` files with one command. Your process config lives in code, not on the server.

---

## What is this package?

**Supervisor** is a process control system for Linux: it keeps long-running processes (queue workers, Horizon, Reverb, etc.) running and restarts them when they crash. You configure it by placing `.conf` files in a directory (e.g. `/etc/supervisor/conf.d/`).

**laratusk/supervise** is a Laravel package that:

- Lets you define those processes in `config/supervise.php` (worker name + command + any Supervisor options).
- Compiles that config into valid Supervisor `.conf` files.
- Can symlink them into your system Supervisor directory and reload Supervisor.

So instead of editing `.conf` files on each server, you edit one PHP config, commit it, and run `php artisan supervise:compile` (optionally with `--reload`) on deploy. Your worker setup is version-controlled and identical everywhere.

---

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- Supervisor installed on the server (for running the processes)

---

## Installation

```bash
composer require laratusk/supervise
```

Publish config and prepare directories (run once per project):

```bash
php artisan supervise:install
```

This creates `.supervisor/conf.d/`, `storage/logs/supervisor/`, adds `.supervisor/` to `.gitignore`, and publishes `config/supervise.php`.

---

## Quick start

1. **Define workers** in `config/supervise.php` under the `workers` key. Each worker needs a `command` (the exact command line to run). The array key is the worker name.

2. **Compile** to generate `.conf` files:

   ```bash
   php artisan supervise:compile
   ```

3. **Link** them into Supervisor’s config directory (once per server, or when you add workers):

   ```bash
   php artisan supervise:link
   ```

4. **Reload** Supervisor so it picks up changes. You can do this in one step with compile:

   ```bash
   php artisan supervise:compile --reload
   ```

On deploy, many projects only need:

```bash
php artisan supervise:compile --reload
```

(assuming `supervise:link` was already run once on that server).

---

## Configuration

Config file: `config/supervise.php`.

### Top-level keys

| Key | Purpose |
|-----|--------|
| `conf_path` | System directory where Supervisor reads configs. `supervise:link` creates symlinks here. Default: `/etc/supervisor/conf.d`. |
| `output_path` | Local directory (relative to project root) where compiled `.conf` files are written. Default: `.supervisor/conf.d`. Not committed (gitignored). |
| `defaults` | Supervisor [program:x] options applied to every worker. Any worker can override these. |
| `workers` | Your process definitions (see below). |
| `groups` | Optional Supervisor [group:x] definitions: group name => list of worker names. |

### Workers

Each entry in `workers` is one Supervisor program. The **key** is the program name; the **value** is an array of options.

**Required:**

- **`command`** (string) — The command line to run. Examples: `php artisan horizon`, `php artisan queue:work redis --queue=default`, `php artisan reverb:start`, or any other command.

**Optional:**

- **`log`** (bool) — If `true`, sets `stdout_logfile` to `storage/logs/supervisor/{worker_name}.log`.
- Any Supervisor program directive (e.g. `numprocs`, `user`, `directory`, `stopwaitsecs`) to override the same key from `defaults`.

Example:

```php
'workers' => [
    'horizon' => [
        'command' => 'php artisan horizon',
    ],
    'default-queue' => [
        'command'  => 'php artisan queue:work redis --queue=default --tries=3',
        'numprocs' => 3,
    ],
    'reverb' => [
        'command' => 'php artisan reverb:start',
        'log'     => true,
    ],
],
```

### Defaults

The `defaults` array holds standard Supervisor [program:x] directives (`numprocs`, `autostart`, `stopsignal`, `user`, `stdout_logfile`, etc.). They are applied to every worker; workers can override any of them. See the published `config/supervise.php` for the full list and defaults. For the official directive reference, see [Supervisor documentation](http://supervisord.org/configuration.html#program-x-section-values).

### Groups

To define a Supervisor group (so you can control several programs together):

```php
'groups' => [
    'app-workers' => ['horizon', 'default-queue'],
],
```

This generates a `[group:app-workers]` section with `programs=horizon,default-queue`. Worker names must exist under `workers`.

---

## Commands

| Command | Description |
|--------|-------------|
| `php artisan supervise:install` | One-time setup: creates dirs, publishes config, updates `.gitignore`. |
| `php artisan supervise:compile` | Generates `.conf` files from `config/supervise.php` into `output_path`. Overwrites existing files. |
| `php artisan supervise:compile --reload` | Same as above, then runs `supervisorctl reread` and `supervisorctl update`. |
| `php artisan supervise:link` | Creates symlinks from each compiled `.conf` in `output_path` to `conf_path`. Run once per server (or when adding workers). |

---

## Deployment

Typical deploy step:

```bash
php artisan supervise:compile --reload
```

This regenerates config from your codebase and tells Supervisor to reload. No manual `.conf` editing on the server.

---

## Testing

```bash
composer test
```

---

## License

MIT. See [LICENSE.md](LICENSE.md).
