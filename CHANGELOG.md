# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] - 2026-11-15

### Added
- **PowerShell-Native Portainer Setup:** Created `scripts/setup-portainer.ps1` to extract setup tokens, initialize admin, and generate API keys natively on Windows without requiring Python.

### Fixed
- **WindowsApps Python Alias Trap:** Resolved `Python was not found` error in `start.bat` on clean machines without Python by testing execution rather than stub path existence and prioritizing native PowerShell for Portainer operations.
- **Portainer Setup Hangs & Timeouts:** Added pre-flight container status checks and connection retry loops in both `setup-portainer.ps1` and `setup-portainer.py` to prevent hanging when Portainer is initializing or stopped.
- **Graceful Shell Fallback:** Updated `start.sh` to gracefully detect missing Python/PowerShell runtimes and output helpful setup notices.

## [1.2.0] - 2026-11-15

### Added
- **Container Manager (Portainer CE):** Integrated web UI on port 9000 under profile `tools` for container logs, metrics, restarts, and console shell access.
- **Portainer Setup Automation:** Script `scripts/setup-portainer.py` to extract setup token, initialize admin account, and generate persistent API key (`portainer-credentials.txt`, `php/src/portainer-token.json`).
- **Interactive Hub Dashboard:** Dynamic dashboard at `php/src/index.php` displaying active PHP runtime, DB connectivity, host/Docker environment, Portainer credentials, and project links. Backward-compatible across PHP 5.6 to 8.5.
- **Host & Docker Environment Detection:** Added diagnostics `scripts/detect-env.ps1` and `scripts/detect-env.sh` to identify host OS, WSL2 status, and Docker engine mode.
- **Categorized Launchers:** Rebuilt `start.bat` (Windows) and `start.sh` (Linux/macOS) into 5 categorized sections: PHP Runtime, Container Manager, Database Tools, Browser Shortcuts, and Diagnostics.
- **Manual PHP Version Selector:** Sub-menu to start and stop individual PHP versions (5.6, 7.4, 8.1, 8.2, 8.3, 8.4, 8.5) independently.
- **Smart Active-Port Dashboard Detection:** Option 10 automatically detects which PHP container is running and opens its dashboard port without forcing PHP 8.3.
- **Dedicated phpMyAdmin for `mysql-sv`:** Connected phpMyAdmin on port 8081 directly to host MySQL (`mysql-sv:3306`) with zero MariaDB dependencies.
- **Optional Standalone Official MySQL 8.0:** Added official `mysql:8.0` container under profile `mysql` with `mysql_native_password` authentication for machines without an existing `mysql-sv`.
- **Environment Template:** Created `.env.example` and `.env` for configurable container ports and database parameters.

### Changed
- **Removed Port 8080:** Dedicated `php83` strictly to port 8336 to release port 8080 on the host and unify standard 4-digit port convention.
- **Manual Restart Policy:** Set `restart: "no"` across all workspace containers in `docker-compose.yml`, while keeping host database containers (`mysql-sv`, `redis-sv`, `postgres-sv`) on `unless-stopped`.
- **Decoupled Portainer:** Assigned `profiles: ["tools", "portainer"]` so Portainer only starts upon explicit user selection.
- **Decoupled Browser Openings:** Removed automatic browser popups on container start options in launchers.

### Fixed
- **CSS / Asset Loading Failure:** Removed `$_SERVER['HTTP_HOST'] = 'localhost:8080'` override in `php/shim.php`, allowing CSS/assets to resolve correctly against any active container port (e.g. port 8512).
- **Windows CMD Launcher Crashes:** Eliminated batch syntax crashes caused by unescaped `&`, delayed expansion, and nested parentheses in parenthesized `if` blocks.

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
