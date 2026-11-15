# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Events dispatched on maintenance activation, deactivation and announcement.
- `Retry-After` header on the maintenance 503 response.
- `SECURITY.md` and issue/pull request templates.

### Changed

- Bypass cookies are invalidated when a new maintenance starts.

## [1.2.0] - 2026-11-01

### Added

- `excluded_routes` level option: listed routes stay open to everyone.
- `status.page` variable in banner templates; the banner is hidden on excluded routes for people who cannot bypass the level.
- `maintenance:status` command.
- Maintenance duration (`ends_at`) and announcements.

## [1.1.2] - 2026-10-23

### Fixed

- Bypass cookie handling.

## [1.1.1] - 2026-10-23

### Changed

- README corrections, test suite, coding standards, static analysis and CI.

## [1.1.0] - 2026-10-23

### Changed

- Improved command usage.

## [1.0.3] - 2026-10-22

### Changed

- Renamed the command call.

### Fixed

- All bundle services are available in dev projects.

## [1.0.2] - 2026-10-22

### Fixed

- Autoload `bundle.php`.

## [1.0.1] - 2026-10-22

### Added

- Symfony 6.4, 7 and 8 support.

## [1.0.0] - 2026-10-22

- Initial release.

[Unreleased]: https://github.com/Mathis-Vincent/maintenance-bundle/compare/1.2.0...HEAD
[1.2.0]: https://github.com/Mathis-Vincent/maintenance-bundle/compare/1.1.2...1.2.0
[1.1.2]: https://github.com/Mathis-Vincent/maintenance-bundle/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/Mathis-Vincent/maintenance-bundle/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/Mathis-Vincent/maintenance-bundle/compare/1.0.3...1.1.0
[1.0.3]: https://github.com/Mathis-Vincent/maintenance-bundle/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/Mathis-Vincent/maintenance-bundle/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/Mathis-Vincent/maintenance-bundle/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/Mathis-Vincent/maintenance-bundle/releases/tag/1.0.0
