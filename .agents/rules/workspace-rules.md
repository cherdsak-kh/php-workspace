---
trigger: always_on
---

# Workspace Rules for php-workspace

1. Follow all guidelines in [AGENTS.md](../AGENTS.md).
2. Never modify files inside `php/src/<project>/` unless the user explicitly requests code changes.
3. When testing projects, test across the appropriate PHP ports (5640, 7433, 8131, 8235, 8336, 8427, 8512).
4. When testing root vs subfolder access, remember that `http://<project>.localhost:<port>/` maps to the project at root `/`, and `http://localhost:<port>/<project>/` maps as a subfolder.
