# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [12.0.2] - 2026-08-08

### Added
- Automated GitHub Release creation on tag push in CI workflow with notes extracted from CHANGELOG.md using AWK.

## [12.0.1] - 2026-08-08

### Added
- Unified support for Laravel 9.x, 10.x, 11.x, and 12.x in a single release.
- CI matrix testing across PHP 8.2, 8.3, and 8.4 with lowest and stable dependency sets.

### Changed
- Expanded `illuminate/mail` and `illuminate/http` requirements in `composer.json` to `^9.0 || ^10.0 || ^11.0 || ^12.0`.
- Simplified coverage badge generation path in GitHub Actions workflow to `coverage/coverage.svg`.

## [12.0.0] - 2025-03-03

### Added
- Support for Laravel 12.x.
- Code coverage badges in CI pipeline.

## [11.0.0] - 2025-03-03

### Added
- Support for Laravel 11.x.

## [10.0.0] - 2025-03-04

### Added
- Support for Laravel 10.x.

## [9.0.0] - 2025-03-04

### Added
- Initial release of Notisend mail driver for Laravel 9.x.
- Support for HTML and plain text emails.
- File attachment handling via API request.
- Message ID and status header parsing (`X-Notisend-MessageId`, `X-Notisend-Status`).
