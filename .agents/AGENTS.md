# AGENTS.md - Antigravity Workspace Guidelines (php-workspace)

Welcome to **PHP Multi-Version Hub** (`php-workspace`).
This workspace provides a centralized, multi-version PHP environment (PHP 5.6 to 8.5) running side-by-side using Docker Compose. All projects reside in a single shared directory: `php/src/<project-name>/`.

---

## 1. Architecture Overview

### Ports & PHP Versions
All containers run simultaneously and mount `./php/src` into `/var/www/html`:

| Service | PHP Version | Port(s) | Typical Use Cases |
| :--- | :--- | :--- | :--- |
| **`php56`** | 5.6.40 | `5640` | Legacy projects (mysql extension, old CMS) |
| **`php74`** | 7.4.33 | `7433` | Transitional legacy (Smarty 3/4, early modern PHP) |
| **`php81`** | 8.1.34 | `8131` | Modern PHP (early strict mode) |
| **`php82`** | 8.2.35 | `8235` | Modern active projects |
| **`php83`** | 8.3.36 | `8336` | Default modern standard |
| **`php84`** | 8.4.27 | `8427` | Latest stable |
| **`php85`** | 8.5.12 | `8512` | Next-generation / Experimental |

### Two URL Access Modes (No `hosts` editing needed!)
1. **Subfolder Mode:**
   `http://localhost:<PORT>/<project-name>/`
   *(Ideal for projects written with a subfolder path, like `php-smarty-sample`)*
2. **Root Domain Mode (`*.localhost`):**
   `http://<project-name>.localhost:<PORT>/`
   *(Modern browsers resolve `*.localhost` to `127.0.0.1` automatically. Apache `mod_vhost_alias` maps this dynamically to `/var/www/html/<project-name>/` as the document root `/`!)*

### Database (`hostname: "DB"`)
- Every PHP container maps `DB` -> `host-gateway:3306` via `extra_hosts`.
- Projects with `"hostname" => "DB"` connect to your existing `mysql-sv` on host directly.

---

## 2. Core Agent Invariants & Guidelines

1. **Zero-Touch Project Code:**
   - Projects in `php/src/<project-name>/` are external repositories.
   - Do NOT edit or modify files inside `php/src/` unless explicitly instructed by the user.
2. **Quick Lifecycle Commands:**
   - Start all containers: `docker compose up -d`
   - Check container status: `docker compose ps`
   - View logs: `docker compose logs -f <service>` (e.g. `docker compose logs -f php74`)
