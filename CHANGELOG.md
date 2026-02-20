# Changelog

All notable changes to `laratusk/supervise` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-20

### Added
- Initial release.
- **Commands:** `supervise:install` (first-time setup), `supervise:compile` (generate `.conf` from config), `supervise:link` (symlink to system conf.d).
- **Config-driven workers:** each worker is defined by a required `command` key in `config/supervise.php`; worker name is the array key. Any command (e.g. `php artisan horizon`, `npm run dev`) is supported.
- **Supervisor options:** shared `defaults` with per-worker overrides; optional `log` to use `storage/logs/supervisor/{name}.log`.
- **Groups:** Supervisor `[group:x]` sections via `groups` config.
- **Compile:** `--reload` flag runs `supervisorctl reread && update` after compile.
- PHPStan level 8 (Larastan), Pest test suite, GitHub Actions CI (PHP 8.2–8.4, Laravel 10/11/12).
