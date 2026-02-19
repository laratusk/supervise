# Changelog

All notable changes to `laratusk/supervise` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Initial release
- `supervise:compile` command to compile Supervisor `.conf` files from Laravel config
- `supervise:link` command to symlink compiled files to the system Supervisor conf directory
- `supervise:install` command for first-time setup
- Support for `horizon`, `queue`, and `reverb` worker types
- Supervisor group configuration support
- Blade view templates for extensible `.conf` generation
- Full defaults configuration with all Supervisor program directives
- `--reload` flag on `supervise:compile` to trigger `supervisorctl reread && update`
- PHPStan level 8 compliance with Larastan
- Comprehensive Pest test suite with 90%+ code coverage
- GitHub Actions CI workflow across PHP 8.2/8.3/8.4 and Laravel 10/11/12
