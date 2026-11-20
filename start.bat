@echo off
setlocal
cd /d "%~dp0"
title PHP Multi-Version Hub

:: Check Docker availability (Windows vs WSL)
set "USE_WSL=0"
where docker >nul 2>&1
if errorlevel 1 (
    where wsl >nul 2>&1
    if not errorlevel 1 (
        set "USE_WSL=1"
    ) else (
        echo [ERROR] Neither Docker nor WSL was found on this system.
        pause
        exit /b 1
    )
)

:: Check PowerShell availability (native on Windows 10/11)
set "HAS_POWERSHELL=0"
where powershell >nul 2>&1
if not errorlevel 1 set "HAS_POWERSHELL=1"

:: Check Python availability (Windows vs WSL - avoid WindowsApps stub)
set "PYTHON_CMD="
python -c "exit(0)" >nul 2>&1
if not errorlevel 1 (
    set "PYTHON_CMD=python"
) else (
    py -c "exit(0)" >nul 2>&1
    if not errorlevel 1 (
        set "PYTHON_CMD=py"
    ) else (
        wsl python3 -c "exit(0)" >nul 2>&1
        if not errorlevel 1 (
            set "PYTHON_CMD=wsl python3"
        )
    )
)

:menu
cls
echo ==============================================================
echo              PHP Multi-Version Hub Launcher
echo ==============================================================
echo.
echo  [ PHP RUNTIME SERVICES ]
echo   1) Start Recommended PHP (PHP 7.4 + PHP 8.3)
echo   2) Start Specific PHP Version (Select from 5.6 to 8.5)
echo   3) Start All PHP Containers (PHP 5.6 to 8.5)
echo   4) Stop PHP Containers
echo.
echo  [ CONTAINER MANAGER (PORTAINER) ]
echo   5) Start Portainer Web App (Port 9000)
echo   6) Stop Portainer Web App
echo   7) Setup / Refresh Portainer Token (Saved to File)
echo.
echo  [ DATABASE TOOLS (PHPMYADMIN) ]
echo   8) Start phpMyAdmin (Port 8081 - Connects to mysql-sv)
echo   9) Stop phpMyAdmin
echo.
echo  [ BROWSER SHORTCUTS ]
echo  10) Open Hub Dashboard (Auto-detect active PHP container)
echo  11) Open Portainer UI  (http://localhost:9000)
echo  12) Open phpMyAdmin    (http://localhost:8081)
echo.
echo  [ SYSTEM AND DIAGNOSTICS ]
echo  13) View Container Status (docker compose ps)
echo  14) Detect Host and Docker Environment
echo  15) Stop ALL Running Containers (Full Shutdown)
echo   0) Exit
echo ==============================================================
set "choice="
set /p choice="Select an option [0-15]: "

if "%choice%"=="1" goto start_rec
if "%choice%"=="2" goto menu_specific_php
if "%choice%"=="3" goto start_all_php
if "%choice%"=="4" goto menu_stop_php
if "%choice%"=="5" goto start_portainer
if "%choice%"=="6" goto stop_portainer
if "%choice%"=="7" goto setup_token
if "%choice%"=="8" goto start_pma
if "%choice%"=="9" goto stop_pma
if "%choice%"=="10" goto open_browser
if "%choice%"=="11" goto open_portainer
if "%choice%"=="12" goto open_pma
if "%choice%"=="13" goto view_status
if "%choice%"=="14" goto detect_env
if "%choice%"=="15" goto stop_all
if "%choice%"=="0" goto end

echo Invalid option. Please try again.
ping 127.0.0.1 -n 2 >nul
goto menu

:: -------------------------------------------------------------
:: PHP Operations
:: -------------------------------------------------------------
:start_rec
echo.
echo Starting Recommended PHP containers (PHP 7.4 and PHP 8.3)...
call :run_docker compose up -d php74 php83
echo.
echo [OK] PHP 7.4 running on port 7433
echo [OK] PHP 8.3 running on port 8336
echo.
pause
goto menu

:menu_specific_php
cls
echo ==============================================================
echo              Start Specific PHP Version
echo ==============================================================
echo  [1] PHP 5.6  (Port 5640) - Legacy (mysql ext, old CMS)
echo  [2] PHP 7.4  (Port 7433) - Transitional (Smarty 3/4)
echo  [3] PHP 8.0  (Port 8030) - PHP 8.0 transition
echo  [4] PHP 8.1  (Port 8131) - Early strict mode
echo  [5] PHP 8.2  (Port 8235) - Modern active
echo  [6] PHP 8.3  (Port 8336) - Hub default standard
echo  [7] PHP 8.4  (Port 8427) - Latest stable
echo  [8] PHP 8.5  (Port 8512) - Experimental / next-gen
echo  [B] Back to Main Menu
echo ==============================================================
set "php_ver="
set /p php_ver="Select PHP version to start [1-8, B]: "

if "%php_ver%"=="1" set "SVC=php56" & set "PORT=5640" & goto do_start_specific
if "%php_ver%"=="2" set "SVC=php74" & set "PORT=7433" & goto do_start_specific
if "%php_ver%"=="3" set "SVC=php80" & set "PORT=8030" & goto do_start_specific
if "%php_ver%"=="4" set "SVC=php81" & set "PORT=8131" & goto do_start_specific
if "%php_ver%"=="5" set "SVC=php82" & set "PORT=8235" & goto do_start_specific
if "%php_ver%"=="6" set "SVC=php83" & set "PORT=8336" & goto do_start_specific
if "%php_ver%"=="7" set "SVC=php84" & set "PORT=8427" & goto do_start_specific
if "%php_ver%"=="8" set "SVC=php85" & set "PORT=8512" & goto do_start_specific
if /i "%php_ver%"=="b" goto menu

echo Invalid selection.
ping 127.0.0.1 -n 2 >nul
goto menu_specific_php

:do_start_specific
echo.
echo Starting container %SVC% (Port %PORT%)...
call :run_docker compose up -d %SVC%
echo.
echo [OK] %SVC% is up at http://localhost:%PORT%/
echo.
pause
goto menu

:start_all_php
echo.
echo Starting all PHP containers (PHP 5.6 to 8.5)...
call :run_docker compose up -d php56 php74 php80 php81 php82 php83 php84 php85
echo.
echo [OK] All PHP containers started.
echo.
pause
goto menu

:menu_stop_php
cls
echo ==============================================================
echo                 Stop PHP Containers
echo ==============================================================
echo  [1] Stop ALL PHP Containers (5.6 to 8.5)
echo  [2] Stop PHP 5.6
echo  [3] Stop PHP 7.4
echo  [4] Stop PHP 8.0
echo  [5] Stop PHP 8.1
echo  [6] Stop PHP 8.2
echo  [7] Stop PHP 8.3
echo  [8] Stop PHP 8.4
echo  [9] Stop PHP 8.5
echo  [B] Back to Main Menu
echo ==============================================================
set "stop_choice="
set /p stop_choice="Select option [1-9, B]: "

if "%stop_choice%"=="1" (
    echo.
    echo Stopping all PHP containers...
    call :run_docker compose stop php56 php74 php80 php81 php82 php83 php84 php85
    echo [OK] All PHP containers stopped.
    pause
    goto menu
)
if "%stop_choice%"=="2" set "STOPSVC=php56" & goto do_stop_specific
if "%stop_choice%"=="3" set "STOPSVC=php74" & goto do_stop_specific
if "%stop_choice%"=="4" set "STOPSVC=php80" & goto do_stop_specific
if "%stop_choice%"=="5" set "STOPSVC=php81" & goto do_stop_specific
if "%stop_choice%"=="6" set "STOPSVC=php82" & goto do_stop_specific
if "%stop_choice%"=="7" set "STOPSVC=php83" & goto do_stop_specific
if "%stop_choice%"=="8" set "STOPSVC=php84" & goto do_stop_specific
if "%stop_choice%"=="9" set "STOPSVC=php85" & goto do_stop_specific
if /i "%stop_choice%"=="b" goto menu

echo Invalid selection.
ping 127.0.0.1 -n 2 >nul
goto menu_stop_php

:do_stop_specific
echo.
echo Stopping container %STOPSVC%...
call :run_docker compose stop %STOPSVC%
echo [OK] Container %STOPSVC% stopped.
pause
goto menu

:: -------------------------------------------------------------
:: Portainer Operations
:: -------------------------------------------------------------
:start_portainer
echo.
echo Starting Portainer container...
call :run_docker compose up -d portainer
echo.
echo Waiting for Portainer to initialize...
ping 127.0.0.1 -n 3 >nul
echo.
echo Initializing Portainer token and credentials...
call :setup_portainer
echo.
echo [OK] Portainer is running at http://localhost:9000
echo.
pause
goto menu

:stop_portainer
echo.
echo Stopping Portainer container...
call :run_docker compose stop portainer
echo [OK] Portainer container stopped.
pause
goto menu

:setup_token
echo.
echo Fetching Portainer Setup Token and API Key...
call :setup_portainer
echo.
pause
goto menu

:: -------------------------------------------------------------
:: Database Tools (phpMyAdmin)
:: -------------------------------------------------------------
:start_pma
echo.
call :ensure_mysql
echo Starting phpMyAdmin...
call :run_docker compose up -d phpmyadmin
echo.
echo [OK] phpMyAdmin running at http://localhost:8081/
echo      Connected to: MySQL (Port 3306)
echo.
pause
goto menu

:stop_pma
echo.
echo Stopping phpMyAdmin...
call :run_docker compose stop phpmyadmin
echo [OK] phpMyAdmin container stopped.
pause
goto menu

:: -------------------------------------------------------------
:: Browser Shortcuts
:: -------------------------------------------------------------
:open_browser
echo.
echo Checking for active PHP containers...
set "ACTIVE_PORT="
set "ACTIVE_PHP="

call :check_php_running php83 8336
if defined ACTIVE_PORT goto do_open_browser
call :check_php_running php85 8512
if defined ACTIVE_PORT goto do_open_browser
call :check_php_running php74 7433
if defined ACTIVE_PORT goto do_open_browser
call :check_php_running php84 8427
if defined ACTIVE_PORT goto do_open_browser
call :check_php_running php82 8235
if defined ACTIVE_PORT goto do_open_browser
call :check_php_running php81 8131
if defined ACTIVE_PORT goto do_open_browser
call :check_php_running php80 8030
if defined ACTIVE_PORT goto do_open_browser
call :check_php_running php56 5640
if defined ACTIVE_PORT goto do_open_browser

echo.
echo  [*] Notice: No PHP container is currently running.
echo      Opening the browser now would show 'Connection Refused'.
echo.
set "start_choice=Y"
set /p start_choice="  Would you like to start PHP 8.3 now? [Y/n]: "
if /i "%start_choice%"=="n" goto menu

echo.
echo Starting php83...
call :run_docker compose up -d php83
set "ACTIVE_PORT=8336"
set "ACTIVE_PHP=php83"
ping 127.0.0.1 -n 2 >nul

:do_open_browser
echo Opening Hub Dashboard on %ACTIVE_PHP% (http://localhost:%ACTIVE_PORT%/)...
start http://localhost:%ACTIVE_PORT%/
goto menu

:check_php_running
set "TMP_ID="
if "%USE_WSL%"=="1" (
    for /f "tokens=*" %%i in ('wsl docker compose ps --status running -q %1 2^>nul') do set "TMP_ID=%%i"
) else (
    for /f "tokens=*" %%i in ('docker compose ps --status running -q %1 2^>nul') do set "TMP_ID=%%i"
)
if defined TMP_ID (
    set "ACTIVE_PHP=%1"
    set "ACTIVE_PORT=%2"
)
exit /b 0

:open_portainer
echo.
echo Checking if Portainer container is running...
set "PORTAINER_RUNNING="
if "%USE_WSL%"=="1" (
    for /f "tokens=*" %%i in ('wsl docker compose ps --status running -q portainer 2^>nul') do set "PORTAINER_RUNNING=%%i"
) else (
    for /f "tokens=*" %%i in ('docker compose ps --status running -q portainer 2^>nul') do set "PORTAINER_RUNNING=%%i"
)

if defined PORTAINER_RUNNING goto do_open_portainer

echo.
echo  [*] Notice: Container 'portainer' (Port 9000) is currently STOPPED.
echo      Opening the browser now would show 'Connection Refused'.
echo.
set "start_p_choice=Y"
set /p start_p_choice="  Would you like to start Portainer now? [Y/n]: "
if /i "%start_p_choice%"=="n" goto menu

echo.
echo Starting portainer...
call :run_docker compose up -d portainer
ping 127.0.0.1 -n 3 >nul
call :setup_portainer

:do_open_portainer
echo Opening Portainer in browser...
start http://localhost:9000/
goto menu

:open_pma
echo.
echo Checking if phpMyAdmin container is running...
set "PMA_RUNNING="
if "%USE_WSL%"=="1" (
    for /f "tokens=*" %%i in ('wsl docker compose ps --status running -q phpmyadmin 2^>nul') do set "PMA_RUNNING=%%i"
) else (
    for /f "tokens=*" %%i in ('docker compose ps --status running -q phpmyadmin 2^>nul') do set "PMA_RUNNING=%%i"
)

if defined PMA_RUNNING goto do_open_pma

echo.
echo  [*] Notice: Container 'phpmyadmin' (Port 8081) is currently STOPPED.
echo      Opening the browser now would show 'Connection Refused'.
echo.
set "start_pma_choice=Y"
set /p start_pma_choice="  Would you like to start phpMyAdmin now? [Y/n]: "
if /i "%start_pma_choice%"=="n" goto menu

call :ensure_mysql
echo.
echo Starting phpMyAdmin...
call :run_docker compose up -d phpmyadmin
ping 127.0.0.1 -n 3 >nul

:do_open_pma
echo Opening phpMyAdmin in browser...
start http://localhost:8081/
goto menu

:: -------------------------------------------------------------
:: Diagnostics and System Utilities
:: -------------------------------------------------------------
:view_status
echo.
echo Container Status:
call :run_docker compose ps
echo.
pause
goto menu

:detect_env
echo.
powershell -ExecutionPolicy Bypass -File scripts\detect-env.ps1
echo.
pause
goto menu

:stop_all
echo.
echo Stopping all workspace containers...
call :run_docker compose --profile tools --profile mysql down
echo.
echo [OK] All workspace containers stopped.
pause
goto menu

:ensure_mysql
netstat -ano | findstr ":3306 " >nul 2>&1
if not errorlevel 1 exit /b 0

echo.
echo  [*] Notice: MySQL on port 3306 is not currently listening.
echo      No existing mysql-sv detected on this system.
echo.
set "start_mysql_choice=Y"
set /p start_mysql_choice="  Would you like to start built-in MySQL 8.0? [Y/n]: "
if /i "%start_mysql_choice%"=="n" exit /b 0

echo.
echo Starting built-in MySQL 8.0 container mysql-hub...
call :run_docker compose --profile mysql up -d mysql
ping 127.0.0.1 -n 4 >nul
exit /b 0

:run_docker
if "%USE_WSL%"=="1" (
    wsl docker %*
) else (
    docker %*
)
exit /b %errorlevel%

:setup_portainer
if "%HAS_POWERSHELL%"=="1" (
    powershell -ExecutionPolicy Bypass -File scripts\setup-portainer.ps1
    exit /b %errorlevel%
)
if defined PYTHON_CMD (
    call :run_python scripts/setup-portainer.py
    exit /b %errorlevel%
)
echo [WARNING] Neither PowerShell nor Python was found to run Portainer auto-setup.
echo           You can access Portainer directly at http://localhost:9000
exit /b 0

:run_python
if defined PYTHON_CMD (
    %PYTHON_CMD% %*
    exit /b %errorlevel%
)
echo [ERROR] Python is not installed or available on this system.
exit /b 1

:end
echo Goodbye!
exit /b 0
