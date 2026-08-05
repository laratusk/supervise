# Changelog

All notable changes to `laratusk/supervise` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-08-05

### Fixed
- Widened `phpstan/phpstan` to `^1.12|^2.0`. The old `^1.12` pin forced `larastan/larastan` 2.x, which caps `illuminate/*` at `^11.41.3`, so `composer update` silently resolved the dev environment back to Laravel 11 even though `require` already allowed `illuminate/* ^13.0`. Laravel 13 and Testbench 11 now install by default.

### Removed
- CI workaround that force-required `phpstan ^2.0` / `larastan ^3.0` on the Laravel 12 and 13 matrix legs; the matrix now installs from the package's declared constraints.

## [1.2.0] - 2026-03-26

### Added
- Allow `pestphp/pest` and `pestphp/pest-plugin-laravel` `^4.0`.

### Changed
- Exclude PHP 8.2 from the Laravel 13 CI matrix leg (Laravel 13 requires PHP 8.3+).
- README requirements now list Laravel 13.

### Fixed
- Pint code style: import `Illuminate\Foundation\Application` in `tests/TestCase.php` instead of using a fully qualified name in the docblock (`fully_qualified_strict_types`).

## [1.1.0] - 2026-03-16

### Added
- Laravel 13 support: `illuminate/console` and `illuminate/support` now allow `^13.0`.
- `orchestra/testbench` `^11.0` for the Laravel 13 test environment.
- Laravel 13 added to the GitHub Actions test matrix.

## [1.0.0] - 2026-02-20

### Added
- Initial release.
- **Commands:** `supervise:install` (first-time setup), `supervise:compile` (generate `.conf` from config), `supervise:link` (symlink to system conf.d).
- **Config-driven workers:** each worker is defined by a required `command` key in `config/supervise.php`; worker name is the array key. Any command (e.g. `php artisan horizon`, `npm run dev`) is supported.
- **Supervisor options:** shared `defaults` with per-worker overrides; optional `log` to use `storage/logs/supervisor/{name}.log`.
- **Groups:** Supervisor `[group:x]` sections via `groups` config.
- **Compile:** `--reload` flag runs `supervisorctl reread && update` after compile.
- PHPStan level 8 (Larastan), Pest test suite, GitHub Actions CI (PHP 8.2–8.4, Laravel 10/11/12).
