#!/usr/bin/env bash
# ==============================================================
# Host & Docker Environment Diagnostic Tool (Bash / Unix / WSL)
# ==============================================================

echo "=============================================================="
echo "         Host & Docker Environment Diagnostic Tool"
echo "=============================================================="

# 1. Detect Host OS & Kernel
UNAME_S="$(uname -s)"
PROC_VER=""
if [ -f /proc/version ]; then
    PROC_VER="$(cat /proc/version)"
fi

HOST_OS="Unknown"
DOCKER_ENV="Unknown"

case "$UNAME_S" in
    Darwin)
        HOST_OS="Apple macOS ($(sw_vers -productVersion 2>/dev/null || uname -r))"
        DOCKER_ENV="Docker Desktop for Mac (Virtualization.framework / LinuxKit)"
        ;;
    Linux)
        if echo "$PROC_VER" | grep -qi "microsoft\|wsl"; then
            HOST_OS="Windows (WSL2 - Windows Subsystem for Linux)"
            DOCKER_ENV="Docker Engine inside WSL2 (or Docker Desktop WSL2 Backend)"
        elif echo "$PROC_VER" | grep -qi "linuxkit\|moby"; then
            HOST_OS="macOS (via LinuxKit VM)"
            DOCKER_ENV="Docker Desktop for Mac"
        else
            DISTRO_NAME="$(grep -E '^PRETTY_NAME=' /etc/os-release 2>/dev/null | cut -d= -f2 | tr -d '\"' || uname -s)"
            HOST_OS="Linux Native ($DISTRO_NAME)"
            DOCKER_ENV="Docker Engine (Linux Native Daemon)"
        fi
        ;;
    CYGWIN*|MINGW*|MSYS*)
        HOST_OS="Windows (Git Bash / MinGW environment)"
        DOCKER_ENV="Docker on Windows"
        ;;
    *)
        HOST_OS="$UNAME_S"
        ;;
esac

echo "[+] Host OS:             $HOST_OS"
echo "[+] Docker Environment:  $DOCKER_ENV"
echo "[+] Kernel / Arch:       $(uname -r) ($(uname -m))"

# 2. Check Docker CLI & Daemon
echo ""
echo "[*] Checking Docker daemon status..."
if docker info >/dev/null 2>&1; then
    DOCKER_VER="$(docker version --format '{{.Server.Version}}' 2>/dev/null)"
    DOCKER_OS="$(docker version --format '{{.Server.Os}}/{{.Server.Arch}}' 2>/dev/null)"
    echo "[+] Docker Server:       Connected (v$DOCKER_VER, $DOCKER_OS)"
else
    echo "[-] Docker Server:       Cannot connect to Docker daemon (/var/run/docker.sock)"
fi

# 3. List Active PHP Hub Containers
echo ""
echo "[*] Active Workspace Containers:"
docker compose ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || docker compose ps

echo "=============================================================="
