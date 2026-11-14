# PHP Workspace

A modern, centralized Multi-PHP development hub for managing and testing multiple PHP projects concurrently across different PHP versions.

## 🚀 Architecture Highlights

- **Shared Project Source:** All projects reside under `php/src/<project-name>/` and are mounted concurrently into all PHP containers.
- **Zero-Touch Project Code:** Projects run without modifying their source code or database credentials.
- **Dual Routing Modes:**
  - **Subfolder Mode:** `http://localhost:<port>/<project-name>/`
  - **Root Domain Mode:** `http://<project-name>.localhost:<port>/` (via Apache Dynamic `VirtualDocumentRoot /var/www/html/%1` — zero Windows `hosts` edits needed)
- **Shared Host Database:** All containers connect to the host MySQL database container (`mysql-sv`) via `DB:3306` (`extra_hosts: ["DB:host-gateway", "hostname:host-gateway"]`).

---

## 📦 PHP Version Matrix & Ports

| Service | PHP Version | Apache Port | Description |
| :--- | :---: | :---: | :--- |
| `php56` | **5.6.40** | `5640` | Legacy projects (Debian Stretch archive with mysqli, mysql, gd) |
| `php74` | **7.4.33** | `7433` | PHP 7.4 runtime |
| `php81` | **8.1.34** | `8131` | PHP 8.1 runtime |
| `php82` | **8.2.35** | `8235` | PHP 8.2 runtime |
| `php83` | **8.3.36** | `8336`, `8080` | PHP 8.3 runtime (Primary local dev port) |
| `php84` | **8.4.27** | `8427` | PHP 8.4 runtime |
| `php85` | **8.5.12** | `8512` | PHP 8.5 preview / latest |

---

## 🛠️ Getting Started

### 1. Place Your PHP Project
Place your project folder under `php/src/`:
```text
php-workspace/
  └── php/
      └── src/
          ├── <project-1>/
          └── <project-2>/
```

### 2. Start Containers
Run all or selected PHP versions:
```bash
# Start specific versions (e.g. PHP 7.4 and 8.3)
docker compose up -d --build php74 php83

# Or start all versions
docker compose up -d --build
```

### 3. Access Your Project
- **PHP 7.4 Subfolder:** `http://localhost:7433/<project-name>/`
- **PHP 8.3 Subfolder:** `http://localhost:8336/<project-name>/` (or `http://localhost:8080/<project-name>/`)
- **Root Domain:** `http://<project-name>.localhost:7433/`

---

## 🤖 AI Agent & Verification Commands

This workspace includes Antigravity AI Agent rules and slash commands:
- **Runbook:** `.agents/AGENTS.md`
- **Rules:** `.agents/rules/workspace-rules.md`
- **Slash Command `/check-php`:**
  ```text
  /check-php <project-name> [php-version|port]
  ```
  Automatically validates route accessibility, HTTP response codes, and database connectivity across PHP versions.
