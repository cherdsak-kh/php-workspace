#!/usr/bin/env bash
# ==============================================================
# PHP Multi-Version Hub Launcher (Linux / macOS / WSL)
# ==============================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$DIR"

show_menu() {
    clear
    echo "=============================================================="
    echo "              PHP Multi-Version Hub Launcher"
    echo "=============================================================="
    echo ""
    echo " [ PHP RUNTIME SERVICES ]"
    echo "  1) Start Recommended PHP (PHP 7.4 + PHP 8.3)"
    echo "  2) Start Specific PHP Version (Select from 5.6 to 8.5)"
    echo "  3) Start All PHP Containers (PHP 5.6 to 8.5)"
    echo "  4) Stop PHP Containers"
    echo ""
    echo " [ CONTAINER MANAGER (PORTAINER) ]"
    echo "  5) Start Portainer Web App (Port 9000)"
    echo "  6) Stop Portainer Web App"
    echo "  7) Setup / Refresh Portainer Token (Saved to File)"
    echo ""
    echo " [ DATABASE TOOLS (PHPMYADMIN) ]"
    echo "  8) Start phpMyAdmin (Port 8081 - Connects to mysql-sv)"
    echo "  9) Stop phpMyAdmin"
    echo ""
    echo " [ BROWSER SHORTCUTS ]"
    echo " 10) Open Hub Dashboard (Auto-detect active PHP container)"
    echo " 11) Open Portainer UI  (http://localhost:9000)"
    echo " 12) Open phpMyAdmin    (http://localhost:8081)"
    echo ""
    echo " [ SYSTEM AND DIAGNOSTICS ]"
    echo " 13) View Container Status (docker compose ps)"
    echo " 14) Detect Host and Docker Environment"
    echo " 15) Stop ALL Running Containers (Full Shutdown)"
    echo "  0) Exit"
    echo "=============================================================="
    echo -n "Select an option [0-15]: "
}

ensure_mysql() {
    if ! docker compose ps --status running -q mysql 2>/dev/null | grep -q .; then
        if ! (nc -z 127.0.0.1 3306 2>/dev/null || ss -tuln | grep -q ':3306 '); then
            echo ""
            echo " [*] Notice: MySQL (Port 3306) is not currently listening."
            echo "     No existing 'mysql-sv' detected on this system."
            read -rp "     Would you like to start built-in MySQL 8.0? [Y/n]: " ans
            if [[ ! "$ans" =~ ^[Nn] ]]; then
                echo " Starting built-in MySQL 8.0 container (mysql-hub)..."
                docker compose --profile mysql up -d mysql
                sleep 3
            fi
        fi
    fi
}

setup_portainer() {
    if command -v python3 >/dev/null 2>&1; then
        python3 scripts/setup-portainer.py
    elif command -v pwsh >/dev/null 2>&1; then
        pwsh -File scripts/setup-portainer.ps1
    else
        echo " [!] Notice: Neither python3 nor pwsh was found to auto-setup Portainer tokens."
        echo "     You can access Portainer directly at http://localhost:9000"
    fi
}

ensure_running() {
    local svc="$1"
    local port="$2"
    if ! docker compose ps --status running -q "$svc" 2>/dev/null | grep -q .; then
        echo ""
        echo " [!] Notice: Container '$svc' (Port $port) is currently STOPPED."
        read -rp "     Would you like to start it now? [Y/n]: " ans
        if [[ "$ans" =~ ^[Nn] ]]; then
            return 1
        fi
        echo " Starting $svc..."
        docker compose up -d "$svc"
        sleep 1
    fi
    return 0
}

open_url() {
    local url="$1"
    if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$url" >/dev/null 2>&1 &
    elif command -v open >/dev/null 2>&1; then
        open "$url" >/dev/null 2>&1 &
    else
        echo " Please open $url in your browser."
    fi
}

start_specific_php() {
    clear
    echo "=============================================================="
    echo "              Start Specific PHP Version"
    echo "=============================================================="
    echo "  [1] PHP 5.6  (Port 5640) - Legacy (mysql ext, old CMS)"
    echo "  [2] PHP 7.4  (Port 7433) - Transitional (Smarty 3/4)"
    echo "  [3] PHP 8.0  (Port 8030) - PHP 8.0 transition"
    echo "  [4] PHP 8.1  (Port 8131) - Early strict mode"
    echo "  [5] PHP 8.2  (Port 8235) - Modern active"
    echo "  [6] PHP 8.3  (Port 8336) - Hub default standard"
    echo "  [7] PHP 8.4  (Port 8427) - Latest stable"
    echo "  [8] PHP 8.5  (Port 8512) - Experimental / next-gen"
    echo "  [B] Back to Main Menu"
    echo "=============================================================="
    echo -n "Select PHP version to start [1-8, B]: "
    read -r pver
    case "$pver" in
        1) svc="php56"; port="5640" ;;
        2) svc="php74"; port="7433" ;;
        3) svc="php80"; port="8030" ;;
        4) svc="php81"; port="8131" ;;
        5) svc="php82"; port="8235" ;;
        6) svc="php83"; port="8336" ;;
        7) svc="php84"; port="8427" ;;
        8) svc="php85"; port="8512" ;;
        [Bb]) return ;;
        *) echo "Invalid choice."; sleep 1; return ;;
    esac

    echo ""
    echo "Starting container $svc (Port $port)..."
    docker compose up -d "$svc"
    echo "[OK] $svc is up at http://localhost:$port/"
    read -rp "Press Enter to continue..."
}

stop_php_menu() {
    clear
    echo "=============================================================="
    echo "                 Stop PHP Containers"
    echo "=============================================================="
    echo "  [1] Stop ALL PHP Containers (5.6 to 8.5)"
    echo "  [2] Stop PHP 5.6"
    echo "  [3] Stop PHP 7.4"
    echo "  [4] Stop PHP 8.0"
    echo "  [5] Stop PHP 8.1"
    echo "  [6] Stop PHP 8.2"
    echo "  [7] Stop PHP 8.3"
    echo "  [8] Stop PHP 8.4"
    echo "  [9] Stop PHP 8.5"
    echo "  [B] Back to Main Menu"
    echo "=============================================================="
    echo -n "Select option [1-9, B]: "
    read -r schoice
    case "$schoice" in
        1)
            echo ""
            echo "Stopping all PHP containers..."
            docker compose stop php56 php74 php80 php81 php82 php83 php84 php85
            echo "[OK] All PHP containers stopped."
            read -rp "Press Enter to continue..."
            ;;
        2) docker compose stop php56; read -rp "Stopped php56. Press Enter..." ;;
        3) docker compose stop php74; read -rp "Stopped php74. Press Enter..." ;;
        4) docker compose stop php80; read -rp "Stopped php80. Press Enter..." ;;
        5) docker compose stop php81; read -rp "Stopped php81. Press Enter..." ;;
        6) docker compose stop php82; read -rp "Stopped php82. Press Enter..." ;;
        7) docker compose stop php83; read -rp "Stopped php83. Press Enter..." ;;
        8) docker compose stop php84; read -rp "Stopped php84. Press Enter..." ;;
        9) docker compose stop php85; read -rp "Stopped php85. Press Enter..." ;;
        [Bb]) return ;;
        *) echo "Invalid choice."; sleep 1 ;;
    esac
}

while true; do
    show_menu
    read -r choice
    case "$choice" in
        1)
            echo ""
            echo "Starting PHP 7.4 and PHP 8.3..."
            docker compose up -d php74 php83
            echo "Hub Dashboard: http://localhost:8336/"
            read -rp "Press Enter to continue..."
            ;;
        2)
            start_specific_php
            ;;
        3)
            echo ""
            echo "Starting all PHP containers (PHP 5.6 to 8.5)..."
            docker compose up -d php56 php74 php80 php81 php82 php83 php84 php85
            echo "Hub Dashboard: http://localhost:8336/"
            read -rp "Press Enter to continue..."
            ;;
        4)
            stop_php_menu
            ;;
        5)
            echo ""
            echo "Starting Portainer..."
            docker compose up -d portainer
            sleep 2
            setup_portainer 2>/dev/null || true
            echo "[OK] Portainer running at http://localhost:9000"
            read -rp "Press Enter to continue..."
            ;;
        6)
            echo ""
            echo "Stopping Portainer..."
            docker compose stop portainer
            echo "[OK] Portainer stopped."
            read -rp "Press Enter to continue..."
            ;;
        7)
            echo ""
            setup_portainer
            read -rp "Press Enter to continue..."
            ;;
        8)
            echo ""
            ensure_mysql
            echo "Starting phpMyAdmin..."
            docker compose up -d phpmyadmin
            echo "[OK] phpMyAdmin: http://localhost:8081/ (Connected to MySQL 3306)"
            read -rp "Press Enter to continue..."
            ;;
        9)
            echo ""
            echo "Stopping phpMyAdmin..."
            docker compose stop phpmyadmin
            echo "[OK] phpMyAdmin container stopped."
            read -rp "Press Enter to continue..."
            ;;
        10)
            active_port=""
            active_php=""
            for s in php83:8336 php85:8512 php74:7433 php84:8427 php82:8235 php81:8131 php80:8030 php56:5640; do
                svc="${s%%:*}"
                port="${s##*:}"
                if docker compose ps --status running -q "$svc" 2>/dev/null | grep -q .; then
                    active_php="$svc"
                    active_port="$port"
                    break
                fi
            done

            if [[ -n "$active_port" ]]; then
                echo "Opening Hub Dashboard on $active_php (http://localhost:$active_port/)..."
                open_url "http://localhost:$active_port/"
            else
                echo ""
                echo " [!] Notice: No PHP container is currently running."
                read -rp "     Would you like to start PHP 8.3 now? [Y/n]: " ans
                if [[ ! "$ans" =~ ^[Nn] ]]; then
                    docker compose up -d php83
                    sleep 1
                    open_url "http://localhost:8336/"
                fi
            fi
            sleep 1
            ;;
        11)
            if ensure_running portainer 9000; then
                setup_portainer 2>/dev/null || true
                echo "Opening Portainer UI..."
                open_url "http://localhost:9000/"
            fi
            sleep 1
            ;;
        12)
            ensure_mysql
            if ensure_running phpmyadmin 8081; then
                echo "Opening phpMyAdmin..."
                open_url "http://localhost:8081/"
            fi
            sleep 1
            ;;
        13)
            echo ""
            docker compose ps
            echo ""
            read -rp "Press Enter to continue..."
            ;;
        14)
            echo ""
            bash scripts/detect-env.sh
            read -rp "Press Enter to continue..."
            ;;
        15)
            echo ""
            echo "Stopping all workspace containers..."
            docker compose --profile tools --profile mysql down
            read -rp "Press Enter to continue..."
            ;;
        0)
            echo "Goodbye!"
            exit 0
            ;;
        *)
            echo "Invalid option."
            sleep 1
            ;;
    esac
done
