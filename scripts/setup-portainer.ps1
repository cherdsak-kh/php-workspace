<#
.SYNOPSIS
    Portainer CE Token & Setup Utility for PHP Workspace (PowerShell Native)
    Extracts setup token from logs, initializes admin user, generates persistent API token,
    and saves credentials to portainer-credentials.txt and php/src/portainer-token.json
#>

[CmdletBinding()]
param()

$WorkspaceRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$PortainerUrl = if ($env:PORTAINER_URL) { $env:PORTAINER_URL } else { "http://localhost:9000" }
$AdminUser = if ($env:PORTAINER_ADMIN_USER) { $env:PORTAINER_ADMIN_USER } else { "admin" }
$AdminPass = if ($env:PORTAINER_ADMIN_PASSWORD) { $env:PORTAINER_ADMIN_PASSWORD } else { "AdminHub@2026!" }

$CredentialsFile = Join-Path $WorkspaceRoot "portainer-credentials.txt"
$TokenJsonFile = Join-Path $WorkspaceRoot "php\src\portainer-token.json"

function Test-PortainerContainerRunning {
    $running = ""
    try {
        $running = (& docker ps --filter "name=portainer-hub" --filter "status=running" -q 2>$null)
    } catch {}
    if (-not $running) {
        try {
            $running = (& wsl.exe docker ps --filter "name=portainer-hub" --filter "status=running" -q 2>$null)
        } catch {}
    }
    return [bool]$running
}

function Get-PortainerLogs {
    $logs = ""
    try {
        $logs = (& docker logs portainer-hub 2>&1 | Out-String)
    } catch {}
    if (-not $logs -or $logs -match "command not found" -or $logs -match "The term 'docker' is not recognized" -or $logs -match "Cannot connect to the Docker daemon") {
        try {
            $logs = (& wsl.exe docker logs portainer-hub 2>&1 | Out-String)
        } catch {}
    }
    return $logs
}

function Get-SetupToken([string]$logs) {
    if (-not $logs) { return "" }
    $regex = [regex]"setup_token=([a-f0-9]{32,64})"
    $matches = $regex.Matches($logs)
    if ($matches.Count -gt 0) {
        return $matches[$matches.Count - 1].Groups[1].Value
    }
    return ""
}

function Test-PortainerAdminExists {
    $checkUrl = "$PortainerUrl/api/users/admin/check"
    try {
        $response = Invoke-WebRequest -Uri $checkUrl -Method GET -Headers @{"User-Agent"="PHP-Workspace-Hub"} -UseBasicParsing -TimeoutSec 3 -ErrorAction Stop
        if ($response.StatusCode -eq 200 -or $response.StatusCode -eq 204) {
            return $true
        }
        return $true
    } catch [System.Net.WebException] {
        $webResp = $_.Exception.Response
        if ($webResp) {
            $code = [int]$webResp.StatusCode
            if ($code -eq 404) {
                return $false # Admin does not exist
            }
            return $true
        }
        return $null # Connection failure
    } catch {
        if ($_.Exception.Message -match "404") {
            return $false
        }
        return $null
    }
}

function Initialize-AdminAccount([string]$setupToken) {
    $initUrl = "$PortainerUrl/api/users/admin/init"
    $body = @{ Username = $AdminUser; Password = $AdminPass } | ConvertTo-Json
    $headers = @{
        "Content-Type" = "application/json"
        "X-Setup-Token" = $setupToken
    }
    try {
        $res = Invoke-RestMethod -Uri $initUrl -Method POST -Headers $headers -Body $body -TimeoutSec 10
        return $res
    } catch {
        Write-Host "[-] Init admin failed: $_" -ForegroundColor Yellow
        return $null
    }
}

function Get-JwtAuthToken {
    $authUrl = "$PortainerUrl/api/auth"
    $body = @{ Username = $AdminUser; Password = $AdminPass } | ConvertTo-Json
    $headers = @{ "Content-Type" = "application/json" }
    try {
        $res = Invoke-RestMethod -Uri $authUrl -Method POST -Headers $headers -Body $body -TimeoutSec 10
        return $res.jwt
    } catch {
        Write-Host "[-] Login failed: $_" -ForegroundColor Yellow
        return $null
    }
}

function New-PortainerApiKey([string]$jwt) {
    $tokenUrl = "$PortainerUrl/api/users/1/tokens"
    $body = @{ description = "php-workspace-key"; password = $AdminPass } | ConvertTo-Json
    $headers = @{
        "Content-Type" = "application/json"
        "Authorization" = "Bearer $jwt"
    }
    try {
        $res = Invoke-RestMethod -Uri $tokenUrl -Method POST -Headers $headers -Body $body -TimeoutSec 10
        return $res.rawAPIKey
    } catch {
        Write-Host "[-] API Key creation failed: $_" -ForegroundColor Yellow
        return $null
    }
}

function Register-PortainerEndpoint([string]$jwt) {
    $endpointUrl = "$PortainerUrl/api/endpoints"
    $headers = @{ "Authorization" = "Bearer $jwt" }
    try {
        $endpoints = Invoke-RestMethod -Uri $endpointUrl -Method GET -Headers $headers -TimeoutSec 5
        if ($endpoints -and $endpoints.Count -gt 0) {
            return $true
        }
    } catch {}

    # Register local docker endpoint via multipart form-data
    try {
        $boundary = [System.Guid]::NewGuid().ToString()
        $contentType = "multipart/form-data; boundary=$boundary"
        $bodyLines = @(
            "--$boundary",
            'Content-Disposition: form-data; name="Name"',
            '',
            'local',
            "--$boundary",
            'Content-Disposition: form-data; name="EndpointCreationType"',
            '',
            '1',
            "--$boundary--",
            ''
        )
        $body = $bodyLines -join "`r`n"
        $regHeaders = @{
            "Authorization" = "Bearer $jwt"
            "Content-Type"  = $contentType
        }
        $null = Invoke-RestMethod -Uri $endpointUrl -Method POST -Headers $regHeaders -Body $body -TimeoutSec 10
        return $true
    } catch {
        return $false
    }
}

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "      Portainer CE Token & Setup Utility (PowerShell)      " -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

# 1. First check if Portainer container is running
if (-not (Test-PortainerContainerRunning)) {
    Write-Host "[-] Portainer container 'portainer-hub' is currently STOPPED." -ForegroundColor Yellow
    Write-Host "    Please start Portainer first (Option 5 in launcher)." -ForegroundColor Yellow
    exit 0
}

# 2. Check if Portainer HTTP is accessible (with short retry if just started)
$adminStatus = $null
$retries = 5
while ($retries -gt 0) {
    $adminStatus = Test-PortainerAdminExists
    if ($null -ne $adminStatus) {
        break
    }
    Write-Host "[*] Waiting for Portainer to initialize at $PortainerUrl..." -ForegroundColor Yellow
    Start-Sleep -Seconds 2
    $retries--
}

if ($null -eq $adminStatus) {
    Write-Host "[-] Cannot connect to Portainer at $PortainerUrl" -ForegroundColor Red
    Write-Host "    Container is running, but port 9000 is not responding yet." -ForegroundColor Yellow
    exit 1
}

$logs = Get-PortainerLogs
$setupToken = Get-SetupToken -logs $logs

$jwtToken = ""
$apiKey = ""

if ($adminStatus -eq $false) {
    Write-Host "[*] Portainer admin not initialized yet." -ForegroundColor Yellow
    $tokenRetries = 5
    while (-not $setupToken -and $tokenRetries -gt 0) {
        Start-Sleep -Seconds 2
        $logs = Get-PortainerLogs
        $setupToken = Get-SetupToken -logs $logs
        $tokenRetries--
    }

    if ($setupToken) {
        $shortToken = if ($setupToken.Length -gt 16) { $setupToken.Substring(0, 16) + "..." } else { $setupToken }
        Write-Host "[+] Found Setup Token from container logs: $shortToken" -ForegroundColor Green
        Write-Host "[*] Initializing admin account '$AdminUser'..." -ForegroundColor Cyan
        $initRes = Initialize-AdminAccount -setupToken $setupToken
        if ($initRes) {
            Write-Host "[+] Admin account initialized successfully!" -ForegroundColor Green
        } else {
            Write-Host "[-] Could not initialize admin automatically." -ForegroundColor Red
        }
    } else {
        Write-Host "[-] Setup token not found in logs. Container might have timed out." -ForegroundColor Red
        Write-Host "    You can access Portainer directly at $PortainerUrl to complete first-time setup." -ForegroundColor Yellow
    }
} else {
    Write-Host "[+] Admin account is already configured." -ForegroundColor Green
}

# Authenticate to get tokens
Write-Host "[*] Authenticating with Portainer API..." -ForegroundColor Cyan
$jwtToken = Get-JwtAuthToken

if ($jwtToken) {
    Write-Host "[+] Successfully authenticated (JWT Token obtained)." -ForegroundColor Green
    # Ensure local endpoint is mapped
    $null = Register-PortainerEndpoint -jwt $jwtToken
    # Create persistent API key
    Write-Host "[*] Generating persistent API Key..." -ForegroundColor Cyan
    $apiKey = New-PortainerApiKey -jwt $jwtToken
    if ($apiKey) {
        $shortKey = if ($apiKey.Length -gt 16) { $apiKey.Substring(0, 16) + "..." } else { $apiKey }
        Write-Host "[+] API Key generated: $shortKey" -ForegroundColor Green
    }
}

# Preserve previous API key if creation didn't return a new one but old one exists
if (-not $apiKey -and (Test-Path $TokenJsonFile)) {
    try {
        $existing = Get-Content $TokenJsonFile -Raw | ConvertFrom-Json
        if ($existing.api_key) {
            $apiKey = $existing.api_key
        }
    } catch {}
}

# Write portainer-credentials.txt
$credText = @"
# ========================================================
# Portainer CE Credentials & Tokens
# Generated by php-workspace
# ========================================================

URL:         $PortainerUrl
Username:    $AdminUser
Password:    $AdminPass

Setup Token: $setupToken
Persistent API Key (X-API-Key):
$apiKey

Bearer JWT Token:
$jwtToken

# Example API usage:
# curl -H "X-API-Key: $apiKey" $PortainerUrl/api/endpoints
"@

Set-Content -Path $CredentialsFile -Value $credText -Encoding UTF8
Write-Host "`n[OK] Saved credentials to: $CredentialsFile" -ForegroundColor Green

# Write php/src/portainer-token.json
$summary = [ordered]@{
    portainer_url = $PortainerUrl
    username      = $AdminUser
    password      = $AdminPass
    setup_token   = $setupToken
    api_key       = $apiKey
    jwt_token     = $jwtToken
}
$jsonDir = Split-Path $TokenJsonFile -Parent
if (-not (Test-Path $jsonDir)) {
    New-Item -ItemType Directory -Path $jsonDir -Force | Out-Null
}
$summary | ConvertTo-Json -Depth 4 | Set-Content -Path $TokenJsonFile -Encoding UTF8
Write-Host "[OK] Saved token JSON to: $TokenJsonFile" -ForegroundColor Green

Write-Host "`n============================================================" -ForegroundColor Cyan
Write-Host " Web UI:      $PortainerUrl"
Write-Host " Username:    $AdminUser"
Write-Host " Password:    $AdminPass"
if ($apiKey) {
    Write-Host " API Key:     $apiKey"
}
if ($setupToken) {
    Write-Host " Setup Token: $setupToken"
}
Write-Host "============================================================" -ForegroundColor Cyan
