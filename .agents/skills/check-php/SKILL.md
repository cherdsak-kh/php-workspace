---
name: check-php
description: >-
  Slash command (/check-php <project> [version|port]) to test route accessibility,
  HTTP status codes, and database connectivity for a project in php-workspace-2 across PHP versions.
---

# /check-php `<project>` `[version|port]`

Use this skill when the user runs `/check-php` or asks to test/verify a PHP project in `php-workspace-2`.

## Execution Steps

1. **Verify Project Exists:**
   - Check `php/src/<project>` in `php-workspace-2`. If not found, list available projects in `php/src/`.
2. **Determine Target Ports:**
   - If a specific port or PHP version is requested (e.g. `5.6` -> `5640`, `7.4` -> `7433`, `8.3` -> `8336`), test that port.
   - Otherwise, test across common versions: `5640` (PHP 5.6), `7433` (PHP 7.4), and `8336` (PHP 8.3).
3. **Test Both Access Modes via curl:**
   - **Subfolder Mode:** `http://localhost:<port>/<project>/` and `http://localhost:<port>/<project>/weadmin/`
   - **Root Mode:** `http://<project>.localhost:<port>/` and `http://<project>.localhost:<port>/weadmin/`
   Record HTTP response codes (`200`, `301`, `302`, `404`, `500`), effective URLs, and page titles.
4. **Check PHP Error Logs:**
   - Run `docker compose logs --tail=25 <service>` if a container returns 500 or error.
5. **Report Results in Thai:**
   - Present a clear comparison table showing HTTP Status, URL, and PHP version.
   - Note any code-level findings (e.g. hardcoded HTTPS redirect, DB host setting) without modifying the source code.
