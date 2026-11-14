# PHP Multi-Version Hub (php-workspace)

A centralized, containerized Multi-PHP development hub for managing, running, and testing multiple PHP projects concurrently across PHP 5.6 to 8.5 side-by-side using Docker Compose.

---

## 🚀 Key Features & Architecture

- **Shared Project Directory:** All projects reside under `php/src/<project-name>/` and are mounted concurrently into all PHP containers.
- **Zero-Touch Project Code:** Projects run without modifying their source code or database credentials.
- **Dual Routing Modes (Zero `hosts` file editing):**
  - **Subfolder Mode:** `http://localhost:<PORT>/<project-name>/`
  - **Root Domain Mode:** `http://<project-name>.localhost:<PORT>/` (Dynamic Apache `VirtualDocumentRoot /var/www/html/%1` resolves automatically in modern browsers).
- **Interactive Hub Dashboard:** Visiting `http://localhost:<PORT>/` opens a live web dashboard showing active PHP runtime, database connectivity status, and a list of all detected projects.
- **Container Management Web App (Portainer CE):** Integrated web UI at `http://localhost:9000` to start, stop, restart, inspect logs, monitor resource metrics, and open terminal consoles inside any PHP container.
- **Flexible Database Connectivity:**
  - **Host MySQL:** Connects to host MySQL container (`mysql-sv`) via `DB:3306` (`host-gateway`).
  - **phpMyAdmin (Port 8081):** Web UI connecting directly to host `mysql-sv`.
- **One-Click Launchers:** Interactive CLI menus for Windows (`start.bat`) and Linux/macOS (`start.sh`).

---

## 📦 PHP Version Matrix & Default Ports

| Service | PHP Version | Port(s) | Description | Typical Use Cases |
| :--- | :---: | :---: | :--- | :--- |
| **`php56`** | **5.6.40** | `5640` | Legacy runtime (Debian Stretch archive, mysql/mysqli/gd) | Old CMS, legacy PHP 5.x apps |
| **`php74`** | **7.4.33** | `7433` | Transitional PHP 7 | Smarty 3/4, transitional projects |
| **`php81`** | **8.1.34** | `8131` | Early strict mode | Early modern PHP |
| **`php82`** | **8.2.35** | `8235` | Active modern | Modern stable frameworks |
| **`php83`** | **8.3.36** | `8336` | Default modern standard | Primary local development port |
| **`php84`** | **8.4.27** | `8427` | Latest stable | Latest features & packages |
| **`php85`** | **8.5.12** | `8512` | Next-generation / Experimental | Experimental testing & preview |
| **`portainer`** | **Portainer CE** | `9000` | Web UI for Docker container management | Start/Stop/Restart, Logs, Exec Shell, Stats |

---

## 🛠️ Quick Start (3 Steps)

### Step 1: Clone Workspace & Configure Environment
```bash
git clone <php-workspace-repo-url>
cd php-workspace

# Optional: adjust ports or DB settings if needed (defaults work out of the box)
cp .env.example .env
```

### Step 2: Start Containers

#### Option A: Using One-Click Launcher
- **Windows:** Double-click or run `start.bat`
- **Linux / macOS / WSL:** Run `./start.sh`

#### Option B: Using Docker Compose CLI
```bash
# 1. Start recommended pair (PHP 7.4 + PHP 8.3)
docker compose up -d php74 php83

# 2. Or start a specific PHP version manually
docker compose up -d php74    # Only PHP 7.4
docker compose up -d php83    # Only PHP 8.3
docker compose up -d php56    # Only PHP 5.6

# 3. Or start ALL PHP versions (5.6 to 8.5)
docker compose up -d php56 php74 php81 php82 php83 php84 php85

# 4. Start Portainer Web App independently (Port 9000)
docker compose up -d portainer

# 5. Start phpMyAdmin independently (Port 8081 -> connects to mysql-sv)
docker compose up -d phpmyadmin
```

### Step 3: Add Your Projects & Access
Clone or place your projects inside `php/src/`:
```bash
git clone <your-project-repo> php/src/my-project
```

Open your browser:
- **Hub Dashboard:** `http://localhost:8336/` (or port `7433`, `8512`, etc.)
- **Container Manager (Portainer):** `http://localhost:9000/`
- **Subfolder Mode:** `http://localhost:8336/my-project/`
- **Root Domain Mode:** `http://my-project.localhost:8336/`
- **phpMyAdmin:** `http://localhost:8081/` (Connected to `mysql-sv`)

---

## 🤖 AI Agent Diagnostics & Verification (`/check-php`)

This workspace is integrated with Google Antigravity AI Agent workflows.

When placing a new or existing project in `php/src/`, run:
```text
/check-php <project-name> [php-version]
```

### What `/check-php` does automatically:
1. **Evaluates Optimal PHP Version:** Analyzes dependencies (Smarty, ADODB, DB drivers, PHP syntax) and recommends the best PHP version.
2. **Validates Connectivity & Routes:** Tests HTTP status across Subfolder and Domain modes.
3. **Diagnoses Local Readiness:** Discovers DB name mismatches, path root issues, or missing rewrite rules and proposes exact fixes so the project runs locally out of the box.

---

## 📂 Workspace Structure

```text
php-workspace/
├── .agents/                    # Antigravity AI agent rules and skills
│   ├── AGENTS.md               # Workspace guidelines
│   └── skills/check-php/       # /check-php slash command implementation
├── .env.example                # Environment variables template
├── docker-compose.yml          # Multi-PHP container orchestration
├── scripts/                    # Utilities and automated setup
│   ├── detect-env.ps1          # Host & Docker diagnostic (PowerShell)
│   ├── detect-env.sh           # Host & Docker diagnostic (Bash)
│   └── setup-portainer.py      # Automated Portainer admin & API token extractor
├── start.bat                   # 1-Click launcher for Windows
├── start.sh                    # 1-Click launcher for Linux/macOS
├── php/
│   ├── Dockerfile              # PHP 7.4 - 8.5 multi-version builder
│   ├── Dockerfile-5.6          # PHP 5.6 legacy builder
│   ├── apache-vhost.conf       # Dynamic mod_vhost_alias configuration
│   ├── shim.php                # Auto-prepend compatibility layer
│   └── src/                    # Shared project mount directory
│       ├── .gitkeep            # Preserves directory in git
│       ├── index.php           # Hub Dashboard
│       └── <project-name>/     # Your cloned projects (ignored by git)
└── README.md
```
