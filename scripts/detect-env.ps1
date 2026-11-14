<#
.SYNOPSIS
    Host & Docker Environment Detection Script for PHP Workspace
#>

Write-Host "==============================================================" -ForegroundColor Cyan
Write-Host "         Host & Docker Environment Diagnostic Tool" -ForegroundColor Cyan
Write-Host "==============================================================" -ForegroundColor Cyan

# 1. Host OS Detection
$hostOS = "Windows"
$osVersion = (Get-CimInstance Win32_OperatingSystem).Caption
$osBuild = (Get-CimInstance Win32_OperatingSystem).BuildNumber
Write-Host "[+] Host Platform:       $hostOS ($osVersion, Build $osBuild)" -ForegroundColor Green

# 2. WSL Status Check
$wslInstalled = $false
$wslDistros = @()
try {
    $wslList = & wsl.exe -l -v 2>$null
    if ($LASTEXITCODE -eq 0 -and $wslList) {
        $wslInstalled = $true
        Write-Host "[+] WSL Status:          Installed and Active (WSL2 Available)" -ForegroundColor Green
    } else {
        Write-Host "[-] WSL Status:          Not Installed / Not Running" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[-] WSL Status:          Not Detected" -ForegroundColor Yellow
}

# 3. Docker Runtime Detection
$dockerEngine = "Unknown"
$dockerType = "None"

# Test Windows native docker command
$dockerWin = Get-Command docker -ErrorAction SilentlyContinue
if ($dockerWin) {
    try {
        $info = & docker version --format '{{.Server.Os}}' 2>$null
        if ($info -match 'linux') {
            $dockerEngine = "Docker Desktop for Windows (WSL2 / Hyper-V Engine)"
            $dockerType = "docker-desktop-windows"
        } elseif ($info -match 'windows') {
            $dockerEngine = "Docker Native Windows Containers"
            $dockerType = "windows-native"
        }
    } catch {}
}

# Test WSL docker if Windows docker not available or for WSL verification
if ($wslInstalled -and $dockerType -eq "None") {
    try {
        $wslDocker = & wsl.exe docker version --format '{{.Server.Os}}' 2>$null
        if ($wslDocker -match 'linux') {
            $dockerEngine = "Docker Engine inside WSL2 (Ubuntu / Linux Backend)"
            $dockerType = "wsl-docker"
        }
    } catch {}
}

Write-Host "[+] Docker Environment:  $dockerEngine" -ForegroundColor Green
Write-Host "[+] Execution Mode:      $dockerType" -ForegroundColor Green

# 4. Check Containers Health
Write-Host "`n[*] Active PHP Hub Containers:" -ForegroundColor Cyan
try {
    $psOut = & wsl.exe docker compose ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}" 2>$null
    if ($psOut) {
        Write-Host ($psOut | Out-String)
    } else {
        $psWin = & docker compose ps 2>$null
        Write-Host ($psWin | Out-String)
    }
} catch {
    Write-Host "Could not query containers." -ForegroundColor Red
}

Write-Host "==============================================================" -ForegroundColor Cyan
