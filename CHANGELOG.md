# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-11-14

### Added
- Commit automation skill (`/commit`) adapted for `php-workspace` with Zero-Touch guard, Docker compose validation, and continuous changelog tracking.
- `CHANGELOG.md` adhering to Keep a Changelog and Semantic Versioning.

## [1.0.0] - 2026-11-14

### Added
- Multi-PHP container matrix supporting PHP 5.6, 7.4, 8.1, 8.2, 8.3, 8.4, and 8.5 concurrently.
- Central shared `php/src/` mount with Zero-Touch project code isolation.
- Dual routing mode support: Subfolder (`localhost:<port>/<project>/`) and Root Domain (`<project>.localhost:<port>/`) via Apache Dynamic `VirtualDocumentRoot`.
- MySQL Innovation 26.10 compatibility via host gateway (`extra_hosts: ["DB:host-gateway", "hostname:host-gateway"]`).
- Compatibility shim (`php/shim.php`) for automatic DEV environment normalization and legacy MySQLi error silencing.
- Agent runbook, workspace rules, and `/check-php` verification skill.
