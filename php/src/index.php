<?php
/**
 * PHP Multi-Version Hub - Landing Dashboard
 * Displays workspace health, active PHP runtime, real-time status of each PHP version (Online/Offline),
 * host & Docker environment detection, Portainer credentials/token, and detected projects in php/src/
 */

$currentPort = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80';
$currentHost = isset($_SERVER['HTTP_HOST']) ? explode(':', $_SERVER['HTTP_HOST'])[0] : 'localhost';
$phpVersion = PHP_VERSION;

// Lightweight Ping Endpoint for cross-port health check
if (isset($_GET['ping'])) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'online',
        'php' => PHP_VERSION,
        'port' => $currentPort,
        'time' => microtime(true)
    ]);
    exit;
}

// Port to PHP version mapping
$phpVersions = [
    '5640' => [
        'service' => 'php56',
        'version' => '5.6.40',
        'name' => 'PHP 5.6',
        'tag' => 'Legacy',
        'desc' => 'Legacy projects (Debian Stretch archive, mysql ext, Smarty 2)',
        'badge_color' => '#f59e0b',
    ],
    '7433' => [
        'service' => 'php74',
        'version' => '7.4.33',
        'name' => 'PHP 7.4',
        'tag' => 'Transitional',
        'desc' => 'Transitional projects (Smarty 3/4, MySQLi, GD, Zip)',
        'badge_color' => '#38bdf8',
    ],
    '8131' => [
        'service' => 'php81',
        'version' => '8.1.34',
        'name' => 'PHP 8.1',
        'tag' => 'Strict Types',
        'desc' => 'Early strict types (Enums, Readonly properties, First-class callables)',
        'badge_color' => '#818cf8',
    ],
    '8235' => [
        'service' => 'php82',
        'version' => '8.2.35',
        'name' => 'PHP 8.2',
        'tag' => 'Stable Active',
        'desc' => 'Modern stable active runtime (Readonly classes, DNF types)',
        'badge_color' => '#a855f7',
    ],
    '8336' => [
        'service' => 'php83',
        'version' => '8.3.36',
        'name' => 'PHP 8.3',
        'tag' => 'Default Hub',
        'desc' => 'Standard modern default runtime',
        'badge_color' => '#10b981',
    ],
    '8427' => [
        'service' => 'php84',
        'version' => '8.4.27',
        'name' => 'PHP 8.4',
        'tag' => 'Latest Stable',
        'desc' => 'Latest stable release (Property hooks, Asymmetric visibility)',
        'badge_color' => '#06b6d4',
    ],
    '8512' => [
        'service' => 'php85',
        'version' => '8.5.12',
        'name' => 'PHP 8.5',
        'tag' => 'Next-Gen Preview',
        'desc' => 'Next-gen experimental release preview',
        'badge_color' => '#ec4899',
    ],
];

// 1. Host & Docker Environment Detection
function detectHostEnvironment() {
    $procVersion = @file_get_contents('/proc/version') ?: '';
    $osFamily = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY : (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'Windows' : 'Linux');

    if (stripos($procVersion, 'microsoft') !== false || stripos($procVersion, 'wsl') !== false) {
        return [
            'os' => 'Windows (WSL2)',
            'docker' => 'Docker Desktop / Docker in WSL2',
            'badge' => 'Windows (WSL2)',
            'badge_color' => '#38bdf8',
            'type' => 'wsl',
            'detail' => 'Host: Windows 10/11 &bull; Docker Engine via WSL2 Linux Subsystem'
        ];
    } elseif (stripos($procVersion, 'linuxkit') !== false || stripos($procVersion, 'moby') !== false) {
        return [
            'os' => 'macOS',
            'docker' => 'Docker Desktop for Mac',
            'badge' => 'macOS (Docker Desktop)',
            'badge_color' => '#a855f7',
            'type' => 'mac',
            'detail' => 'Host: Apple macOS &bull; Hypervisor.framework / LinuxKit VM'
        ];
    } elseif ($osFamily === 'Windows') {
        return [
            'os' => 'Windows Native',
            'docker' => 'Docker Desktop (Windows Containers)',
            'badge' => 'Windows Native',
            'badge_color' => '#0ea5e9',
            'type' => 'windows',
            'detail' => 'Host: Windows &bull; Native Windows container process isolation'
        ];
    } elseif ($osFamily === 'Linux') {
        return [
            'os' => 'Linux (Native)',
            'docker' => 'Docker Engine (Linux Native Daemon)',
            'badge' => 'Linux Native',
            'badge_color' => '#10b981',
            'type' => 'linux',
            'detail' => 'Host: Native Linux Server / Workstation (Direct Docker Daemon)'
        ];
    } else {
        return [
            'os' => PHP_OS,
            'docker' => 'Docker Engine',
            'badge' => 'Custom / Other',
            'badge_color' => '#94a3b8',
            'type' => 'other',
            'detail' => 'Custom containerized host platform'
        ];
    }
}
$hostEnv = detectHostEnvironment();

// 2. Database Connection Check (DB:3306)
$dbHost = 'DB';
$dbPort = 3306;
$dbStatus = false;
$dbError = '';
$dbLatency = 0;

$t0 = microtime(true);
$fp = @fsockopen($dbHost, $dbPort, $errno, $errstr, 0.8);
if ($fp) {
    $dbStatus = true;
    $dbLatency = round((microtime(true) - $t0) * 1000, 1);
    fclose($fp);
} else {
    $dbError = $errstr ?: "Cannot reach $dbHost:$dbPort";
}

// 3. Portainer Status & Token Loading
$portainerStatus = false;
$portainerTargets = ['portainer', 'portainer-hub', 'DB', 'host.docker.internal', '127.0.0.1'];
foreach ($portainerTargets as $pTarget) {
    $portainerFp = @fsockopen($pTarget, 9000, $pErrno, $pErrstr, 0.3);
    if ($portainerFp) {
        $portainerStatus = true;
        fclose($portainerFp);
        break;
    }
}

// 3.1 phpMyAdmin Status
$pmaStatus = false;
$pmaTargets = ['phpmyadmin', 'pma-hub', 'DB', 'host.docker.internal', '127.0.0.1'];
foreach ($pmaTargets as $pmaTarget) {
    $pmaPort = ($pmaTarget === 'phpmyadmin' || $pmaTarget === 'pma-hub') ? 80 : 8081;
    $pmaFp = @fsockopen($pmaTarget, $pmaPort, $pErrno, $pErrstr, 0.2);
    if ($pmaFp) {
        $pmaStatus = true;
        fclose($pmaFp);
        break;
    }
}

// Read portainer-token.json if available
$portainerData = null;
$tokenFile = __DIR__ . '/portainer-token.json';
if (file_exists($tokenFile)) {
    $tokenJson = @file_get_contents($tokenFile);
    if ($tokenJson) {
        $portainerData = json_decode($tokenJson, true);
    }
}

// 4. Scan projects in php/src
$projects = [];
$scanDir = __DIR__;
$excludeDirs = ['.', '..', '.git', '_db', 'scratch', 'logs', 'temp'];

if ($handle = opendir($scanDir)) {
    while (false !== ($entry = readdir($handle))) {
        if (!in_array($entry, $excludeDirs) && is_dir($scanDir . '/' . $entry) && substr($entry, 0, 1) !== '.') {
            $hasWeadmin = is_dir($scanDir . '/' . $entry . '/weadmin');
            $hasIndex = file_exists($scanDir . '/' . $entry . '/index.php') || file_exists($scanDir . '/' . $entry . '/index.html');
            $projects[] = [
                'name' => $entry,
                'hasWeadmin' => $hasWeadmin,
                'hasIndex' => $hasIndex,
                'mtime' => filemtime($scanDir . '/' . $entry),
            ];
        }
    }
    closedir($handle);
}
usort($projects, function($a, $b) { return strcmp($a['name'], $b['name']); });

// Essential extensions check
$extensions = ['mysqli', 'pdo_mysql', 'gd', 'mbstring', 'zip', 'curl'];
$extStatus = [];
foreach ($extensions as $ext) {
    $extStatus[$ext] = extension_loaded($ext);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Multi-Version Hub - Port <?= htmlspecialchars($currentPort) ?> (PHP <?= htmlspecialchars($phpVersion) ?>)</title>
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-hover: #334155;
            --border: #334155;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #38bdf8;
            --primary-hover: #0ea5e9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 24px;
            min-height: 100vh;
        }
        .container { max-width: 1240px; margin: 0 auto; }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand h1 { font-size: 24px; font-weight: 700; color: #fff; }
        .header-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .badge-env {
            background: rgba(56, 189, 248, 0.15);
            color: var(--primary);
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
        }
        .badge-php {
            background: #0284c7;
            color: #fff;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
        }
        .btn-portainer {
            background: #032b43;
            color: #38bdf8;
            border: 1px solid #0284c7;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-portainer:hover {
            background: #0284c7;
            color: #fff;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.4);
        }

        /* Top Version Navigation Bar */
        .version-nav {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            background: var(--surface);
            padding: 8px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }
        .nav-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: var(--text-muted);
            text-decoration: none;
            padding: 8px 6px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }
        @media (max-width: 1080px) {
            .version-nav {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        @media (max-width: 640px) {
            .version-nav {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .nav-tab:hover {
            color: #fff;
            background: var(--surface-hover);
            border-color: var(--border);
        }
        .nav-tab.active {
            background: #0284c7;
            color: #fff;
            font-weight: 700;
            border-color: var(--primary);
            box-shadow: 0 0 14px rgba(56, 189, 248, 0.3);
        }
        .nav-tab.is-offline {
            opacity: 0.45;
            cursor: not-allowed !important;
            filter: grayscale(0.7);
        }
        .nav-tab.is-offline:hover {
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.03);
            border-color: transparent;
        }
        .btn.disabled,
        .btn:disabled,
        .btn-disabled,
        .f-link-disabled {
            opacity: 0.45 !important;
            cursor: not-allowed !important;
            filter: grayscale(0.7);
        }
        .nav-tab .nav-port {
            font-size: 11px;
            opacity: 0.75;
            font-family: monospace;
        }

        /* Status Dot Indicator */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
            transition: all 0.3s;
        }
        .status-dot.online {
            background: var(--success);
            box-shadow: 0 0 8px var(--success);
            animation: pulse-green 2s infinite;
        }
        .status-dot.offline {
            background: var(--danger);
            box-shadow: 0 0 4px rgba(239, 68, 68, 0.4);
        }
        .status-dot.checking {
            background: var(--warning);
            box-shadow: 0 0 6px var(--warning);
            animation: pulse-orange 1s infinite;
        }

        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        @keyframes pulse-orange {
            0% { opacity: 0.4; }
            50% { opacity: 1; }
            100% { opacity: 0.4; }
        }

        /* Status Badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-status.online {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-status.offline {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }
        .badge-status.checking {
            background: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }
        .badge-status-subtle {
            font-size: 12px;
            color: #94a3b8;
            padding: 4px 10px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: inline-block;
        }

        /* =========================================================================
           Accordion Component for PHP Runtimes Status Matrix
           ========================================================================= */
        details.runtimes-accordion {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        details.runtimes-accordion[open] {
            border-color: rgba(56, 189, 248, 0.4);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }
        summary.accordion-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            cursor: pointer;
            user-select: none;
            list-style: none;
            background: rgba(255, 255, 255, 0.02);
            transition: background 0.2s ease;
            gap: 16px;
        }
        summary.accordion-summary::-webkit-details-marker {
            display: none;
        }
        summary.accordion-summary:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .summary-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .chevron-icon {
            color: var(--primary);
            transition: transform 0.25s ease;
            flex-shrink: 0;
        }
        details[open] .chevron-icon {
            transform: rotate(180deg);
        }
        .summary-title-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .summary-title-group h2 {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }
        .summary-badges {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .summary-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .accordion-body {
            padding: 0 20px 20px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        .runtimes-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 16px;
        }
        .runtime-row {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            transition: all 0.2s ease;
        }
        .runtime-row:hover {
            border-color: rgba(56, 189, 248, 0.35);
            background: rgba(15, 23, 42, 0.85);
        }
        .runtime-row.is-current {
            border-color: #0284c7;
            background: linear-gradient(90deg, rgba(2, 132, 199, 0.15), rgba(15, 23, 42, 0.7));
            box-shadow: 0 0 12px rgba(2, 132, 199, 0.15);
        }
        .row-main {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }
        .row-title {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .runtime-name {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
        }
        .runtime-tag {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .runtime-port-badge {
            font-family: monospace;
            font-size: 12px;
            color: var(--primary);
            background: rgba(56, 189, 248, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .row-desc {
            font-size: 12px;
            color: var(--text-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .row-desc code {
            color: #38bdf8;
            background: rgba(0, 0, 0, 0.3);
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 11px;
        }
        .row-side {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
        }

        @media (max-width: 860px) {
            .runtime-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .row-main {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
                width: 100%;
            }
            .row-desc {
                white-space: normal;
            }
            .row-side {
                width: 100%;
                justify-content: space-between;
                border-top: 1px dashed rgba(255, 255, 255, 0.06);
                padding-top: 8px;
            }
        }

        /* Guide Accordion Styling */
        .guide-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .guide-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px 16px;
        }
        .guide-title {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .guide-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .guide-list code {
            color: #38bdf8;
            background: rgba(0, 0, 0, 0.3);
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: 600;
        }
        .guide-list strong {
            color: #e2e8f0;
        }
        .guide-footer-tip {
            margin-top: 16px;
            background: rgba(2, 132, 199, 0.1);
            border: 1px solid rgba(2, 132, 199, 0.25);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #bae6fd;
            line-height: 1.5;
        }

        /* Health & Stats Grid */
        .grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .card-value {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pill-group { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .pill {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
        }
        .pill.active { color: var(--success); border-color: rgba(16, 185, 129, 0.4); }
        .pill.inactive { color: var(--text-muted); opacity: 0.5; }

        /* Portainer Token Banner */
        .token-banner {
            background: linear-gradient(135deg, rgba(3, 43, 67, 0.9), rgba(15, 23, 42, 0.9));
            border: 1px solid #0284c7;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .token-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .token-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 12px;
            background: rgba(0, 0, 0, 0.25);
            padding: 14px;
            border-radius: 8px;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .field-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px; }
        .field-val {
            font-family: monospace;
            font-size: 13px;
            color: #fff;
            word-break: break-all;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .btn-copy {
            background: rgba(56, 189, 248, 0.15);
            color: var(--primary);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 4px;
            padding: 2px 8px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-copy:hover {
            background: var(--primary);
            color: #0f172a;
        }
        
        /* Projects Grid */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .section-header h2 { font-size: 18px; font-weight: 600; color: #fff; }
        .project-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }
        .project-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.15s, border-color 0.15s;
        }
        .project-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
        }
        .project-name {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
            word-break: break-all;
        }
        .project-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 14px;
        }
        .project-version-control {
            margin-bottom: 14px;
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 10px 12px;
        }
        .control-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .control-label svg {
            color: var(--primary);
        }
        .version-select {
            width: 100%;
            background: #0f172a;
            color: #e2e8f0;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            transition: all 0.2s ease;
        }
        .version-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25);
        }
        .version-select option {
            background: #1e293b;
            color: #f1f5f9;
        }
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            gap: 6px;
        }
        .btn-primary { background: #0284c7; color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary { background: var(--surface-hover); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: #475569; color: #fff; }
        .btn-admin { background: #334155; color: #38bdf8; border: 1px solid #0284c7; }
        .btn-admin:hover { background: #0284c7; color: #fff; }
        .empty-state {
            background: var(--surface);
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 48px;
            text-align: center;
            color: var(--text-muted);
        }
        .code-box {
            background: #0f172a;
            padding: 12px 16px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 13px;
            color: #38bdf8;
            margin: 16px auto;
            display: inline-block;
        }
        /* =========================================================================
           Modern Hub Footer
           ========================================================================= */
        footer {
            margin-top: 56px;
            padding-top: 36px;
            border-top: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 13px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 48px;
            margin-bottom: 32px;
        }
        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
        }
        .footer-col {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
        }
        .footer-desc {
            font-size: 13px;
            line-height: 1.6;
            color: var(--text-muted);
            margin: 0;
        }
        .footer-status-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 4px;
        }
        .f-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            color: #e2e8f0;
        }
        .footer-heading {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 4px 0;
        }
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .footer-links li a,
        .footer-links li span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.15s ease;
        }
        .footer-links li a:hover {
            color: var(--primary);
        }
        .f-icon {
            font-size: 14px;
        }
        .f-port {
            font-family: monospace;
            font-size: 11px;
            background: rgba(56, 189, 248, 0.1);
            color: var(--primary);
            padding: 1px 6px;
            border-radius: 4px;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .footer-bottom {
            padding: 20px 0 10px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12px;
        }
        .footer-bottom strong {
            color: #f1f5f9;
        }
        .footer-bottom code {
            color: #38bdf8;
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="brand">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                <div>
                    <h1>PHP Multi-Version Hub</h1>
                    <span style="font-size: 13px; color: var(--text-muted);">Shared source: <code>php/src/</code> &bull; Container: <?= htmlspecialchars(gethostname()) ?></span>
                </div>
            </div>
            <div class="header-actions">
                <span class="badge-env" title="<?= htmlspecialchars($hostEnv['detail']) ?>">
                    💻 <?= htmlspecialchars($hostEnv['badge']) ?>
                </span>
                <a href="http://<?= htmlspecialchars($currentHost) ?>:9000/" target="_blank" class="btn-portainer <?= $portainerStatus ? '' : 'btn-disabled' ?>" id="header-btn-portainer" title="<?= $portainerStatus ? 'Manage containers, view logs, inspect stats' : 'Portainer ปิดอยู่ (กรุณาเปิดผ่าน start.bat)' ?>">
                    🐳 Container Manager (Portainer)
                </a>
                <span class="badge-php">Current: PHP <?= htmlspecialchars($phpVersion) ?> (Port <?= htmlspecialchars($currentPort) ?>)</span>
            </div>
        </header>

        <!-- PHP Version Switcher (Top Bar) -->
        <div class="version-nav">
            <?php foreach ($phpVersions as $p => $info): 
                $isCurrent = ($currentPort == $p);
            ?>
                <a href="http://<?= htmlspecialchars($currentHost) ?>:<?= $p ?>/" 
                   id="nav-link-<?= $p ?>" 
                   class="nav-tab <?= $isCurrent ? 'active' : '' ?>" 
                   title="<?= htmlspecialchars($info['desc']) ?>">
                    <span class="status-dot <?= $isCurrent ? 'online' : 'checking' ?>" id="nav-dot-<?= $p ?>"></span>
                    <span class="nav-name"><?= htmlspecialchars($info['name']) ?></span>
                    <span class="nav-port">:<?= $p ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Real-time PHP Runtimes Status Matrix (Accordion) -->
        <details class="runtimes-accordion" id="runtimes-accordion">
            <summary class="accordion-summary">
                <div class="summary-left">
                    <svg class="chevron-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                    <div class="summary-title-group">
                        <h2>สถานะ PHP Runtimes (Version Status Matrix)</h2>
                        <div class="summary-badges">
                            <span class="badge-status online" id="sum-badge-online">
                                <span class="status-dot online"></span>
                                <span id="sum-online-text">... เปิดอยู่</span>
                            </span>
                            <span class="badge-status offline" id="sum-badge-offline">
                                <span class="status-dot offline"></span>
                                <span id="sum-offline-text">... ปิดอยู่</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="summary-right">
                    <button id="btn-refresh-status" class="btn btn-secondary btn-sm" onclick="event.preventDefault(); checkAllPhpVersions(true);" title="ตรวจสอบสถานะพอร์ตทั้งหมดอีกครั้ง">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                        </svg>
                        <span>รีเฟรชสถานะ</span>
                    </button>
                </div>
            </summary>

            <div class="accordion-body">
                <div class="runtimes-list">
                    <?php foreach ($phpVersions as $p => $info): 
                        $isCurrent = ($currentPort == $p);
                    ?>
                        <div class="runtime-row <?= $isCurrent ? 'is-current' : '' ?>" id="card-runtime-<?= $p ?>">
                            <div class="row-main">
                                <span class="status-dot <?= $isCurrent ? 'online' : 'checking' ?>" id="row-dot-<?= $p ?>"></span>
                                <div class="row-title">
                                    <span class="runtime-tag" style="background: <?= $info['badge_color'] ?>22; color: <?= $info['badge_color'] ?>; border: 1px solid <?= $info['badge_color'] ?>44;">
                                        <?= htmlspecialchars($info['tag']) ?>
                                    </span>
                                    <strong class="runtime-name"><?= htmlspecialchars($info['name']) ?></strong>
                                    <span class="runtime-port-badge">:<?= $p ?></span>
                                </div>
                                <div class="row-desc">
                                    <span class="meta-label">Version:</span> <code><?= htmlspecialchars($info['version']) ?></code>
                                    &bull; <span class="meta-label">Service:</span> <code><?= htmlspecialchars($info['service']) ?></code>
                                    &bull; <span><?= htmlspecialchars($info['desc']) ?></span>
                                </div>
                            </div>

                            <div class="row-side">
                                <div class="runtime-status-badge" id="card-badge-<?= $p ?>">
                                    <?php if ($isCurrent): ?>
                                        <span class="badge-status online">
                                            <span class="status-dot online"></span>
                                            <span>เปิดอยู่ (Active)</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-status checking">
                                            <span class="status-dot checking"></span>
                                            <span>กำลังตรวจ...</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>

        <!-- start.bat Launcher Quick Guide (Accordion) -->
        <details class="runtimes-accordion" id="guide-accordion">
            <summary class="accordion-summary">
                <div class="summary-left">
                    <svg class="chevron-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                    <div class="summary-title-group">
                        <h2>📖 คู่มือการใช้งาน One-Click Launcher (start.bat)</h2>
                        <span class="badge-status-subtle">เมนูควบคุมคอนเทนเนอร์ 15 คำสั่ง</span>
                    </div>
                </div>
                <div class="summary-right">
                    <span style="font-size: 12px; color: var(--text-muted);">ดับเบิลคลิก <code>start.bat</code> บน Windows</span>
                </div>
            </summary>

            <div class="accordion-body">
                <div class="guide-grid">
                    <div class="guide-card">
                        <div class="guide-title">🚀 1. PHP Runtime Services</div>
                        <ul class="guide-list">
                            <li><code>[1]</code> <strong>Start Recommended</strong> - รัน PHP 7.4 (:7433) + PHP 8.3 (:8336)</li>
                            <li><code>[2]</code> <strong>Start Specific Version</strong> - เลือกรันเฉพาะเวอร์ชัน (5.6 - 8.5)</li>
                            <li><code>[3]</code> <strong>Start All Containers</strong> - รันทุกเวอร์ชันพร้อมกัน (5.6 ถึง 8.5)</li>
                            <li><code>[4]</code> <strong>Stop PHP Containers</strong> - เลือกหยุดคอนเทนเนอร์ทั้งหมดหรือเฉพาะตัว</li>
                        </ul>
                    </div>

                    <div class="guide-card">
                        <div class="guide-title">🐳 2. Container Manager &amp; Database</div>
                        <ul class="guide-list">
                            <li><code>[5 / 6]</code> <strong>Start/Stop Portainer</strong> - จัดการคอนเทนเนอร์ผ่านเว็บ (:9000)</li>
                            <li><code>[7]</code> <strong>Setup Token</strong> - สร้าง/อัปเดต Token และรหัสผ่าน Portainer</li>
                            <li><code>[8 / 9]</code> <strong>Start/Stop phpMyAdmin</strong> - จัดการฐานข้อมูลผ่านเว็บ (:8081)</li>
                        </ul>
                    </div>

                    <div class="guide-card">
                        <div class="guide-title">🌐 3. Browser Shortcuts &amp; Diagnostics</div>
                        <ul class="guide-list">
                            <li><code>[10]</code> <strong>Open Hub Dashboard</strong> - เปิดหน้า Hub ตรวจหา PHP ที่เปิดอยู่อัตโนมัติ</li>
                            <li><code>[11 / 12]</code> <strong>Open Portainer / phpMyAdmin</strong> - เปิดหน้าต่างเครื่องมือในเบราว์เซอร์</li>
                            <li><code>[13 / 14]</code> <strong>View Status &amp; Detect Env</strong> - ตรวจสอบสถานะและเช็คระบบ WSL/Docker</li>
                            <li><code>[15]</code> <strong>Stop ALL</strong> - ปิดคอนเทนเนอร์ทั้งหมดใน Workspace (Full Shutdown)</li>
                        </ul>
                    </div>
                </div>
                <div class="guide-footer-tip">
                    💡 <strong>คำแนะนำ:</strong> หากพอร์ตหรือเวอร์ชันใดในหน้า Hub นี้แสดงสถานะ <span class="badge-status offline" style="font-size: 11px; padding: 1px 6px;">● ปิดอยู่</span> ลิงก์จะถูกล็อกไม่ให้คลิกชั่วคราว เพื่อป้องกันการเชื่อมต่อล้มเหลว สามารถเปิดไฟล์ <code>start.bat</code> เพื่อสั่งเริ่มการทำงาน (Start) คอนเทนเนอร์ที่ต้องการได้ทันที
                </div>
            </div>
        </details>

        <!-- Health & Stats Panel -->
        <div class="grid-stats">
            <!-- 1. Host & Docker Environment Card -->
            <div class="card">
                <div>
                    <div class="card-title">Host &amp; Docker Environment</div>
                    <div class="card-value" style="font-size: 17px; color: <?= htmlspecialchars($hostEnv['badge_color']) ?>;">
                        <?= htmlspecialchars($hostEnv['os']) ?>
                    </div>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
                    <?= $hostEnv['detail'] ?>
                </div>
            </div>

            <!-- 2. Database Gateway Card -->
            <div class="card">
                <div>
                    <div class="card-title">Database Gateway (DB:3306)</div>
                    <div class="card-value">
                        <span class="status-dot <?= $dbStatus ? 'online' : 'offline' ?>"></span>
                        <?= $dbStatus ? "Connected ({$dbLatency} ms)" : "Offline / Unreachable" ?>
                    </div>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
                    <?= $dbStatus ? "Connected to host MySQL (mysql-sv)" : htmlspecialchars($dbError) ?>
                </div>
            </div>

            <!-- 3. Container Manager Card -->
            <div class="card">
                <div>
                    <div class="card-title">Container Manager (Web App)</div>
                    <div class="card-value">
                        <span class="status-dot <?= $portainerStatus ? 'online' : 'offline' ?>"></span>
                        <?= $portainerStatus ? "Portainer Active (:9000)" : "Portainer Offline" ?>
                    </div>
                </div>
                <div style="margin-top: 8px;">
                    <a href="http://<?= htmlspecialchars($currentHost) ?>:9000/" target="_blank" class="btn btn-secondary <?= $portainerStatus ? '' : 'btn-disabled' ?>" id="card-btn-portainer" style="padding: 6px 10px; font-size: 12px; width: 100%;" title="<?= $portainerStatus ? 'Open Portainer Dashboard' : 'Portainer ปิดอยู่ (กรุณาเปิดผ่าน start.bat)' ?>">
                        <?= $portainerStatus ? 'Open Portainer Dashboard &rarr;' : 'Portainer ปิดอยู่ &times;' ?>
                    </a>
                </div>
            </div>

            <!-- 4. PHP Environment & Extensions -->
            <div class="card">
                <div>
                    <div class="card-title">Loaded Extensions</div>
                    <div class="pill-group">
                        <?php foreach ($extStatus as $ext => $loaded): ?>
                            <span class="pill <?= $loaded ? 'active' : 'inactive' ?>">
                                <?= $loaded ? '&#10003; ' : '&#10007; ' ?><?= htmlspecialchars($ext) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">
                    Timezone: <?= htmlspecialchars(date_default_timezone_get()) ?> &bull; Limit: <?= htmlspecialchars(ini_get('memory_limit')) ?>
                </div>
            </div>
        </div>

        <!-- Portainer Setup Token & Credentials Banner -->
        <?php if ($portainerData): ?>
            <div class="token-banner">
                <div class="token-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 18px;">🐳</span>
                        <div>
                            <strong style="color: #fff; font-size: 15px;">Portainer Credentials &amp; Tokens (Auto-Extracted)</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Saved in <code>portainer-token.json</code> &bull; No docker exec needed!</div>
                        </div>
                    </div>
                    <a href="http://<?= htmlspecialchars($currentHost) ?>:9000/" target="_blank" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px;">
                        Login to Portainer &rarr;
                    </a>
                </div>
                <div class="token-fields">
                    <div>
                        <div class="field-label">Username</div>
                        <div class="field-val">
                            <span><?= htmlspecialchars(isset($portainerData['username']) ? $portainerData['username'] : 'admin') ?></span>
                            <button class="btn-copy" onclick="copyText('<?= htmlspecialchars(isset($portainerData['username']) ? $portainerData['username'] : 'admin') ?>', this)">Copy</button>
                        </div>
                    </div>
                    <div>
                        <div class="field-label">Password</div>
                        <div class="field-val">
                            <span><?= htmlspecialchars(isset($portainerData['password']) ? $portainerData['password'] : 'AdminHub@2026!') ?></span>
                            <button class="btn-copy" onclick="copyText('<?= htmlspecialchars(isset($portainerData['password']) ? $portainerData['password'] : 'AdminHub@2026!') ?>', this)">Copy</button>
                        </div>
                    </div>
                    <div>
                        <div class="field-label">Setup Token (Initial Token)</div>
                        <div class="field-val">
                            <span style="font-size: 11px;"><?= htmlspecialchars(substr(isset($portainerData['setup_token']) ? $portainerData['setup_token'] : 'N/A', 0, 22)) ?>...</span>
                            <button class="btn-copy" onclick="copyText('<?= htmlspecialchars(isset($portainerData['setup_token']) ? $portainerData['setup_token'] : '') ?>', this)">Copy</button>
                        </div>
                    </div>
                    <div>
                        <div class="field-label">API Key (X-API-Key)</div>
                        <div class="field-val">
                            <span style="font-size: 11px;"><?= htmlspecialchars(substr(isset($portainerData['api_key']) ? $portainerData['api_key'] : 'N/A', 0, 22)) ?>...</span>
                            <button class="btn-copy" onclick="copyText('<?= htmlspecialchars(isset($portainerData['api_key']) ? $portainerData['api_key'] : '') ?>', this)">Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Projects List -->
        <div class="section-header">
            <h2>Detected Projects (<?= count($projects) ?>)</h2>
            <span style="font-size: 13px; color: var(--text-muted);">Located in <code>./php/src/</code></span>
        </div>

        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <h3>No PHP projects found in <code>php/src/</code></h3>
                <p style="margin-top: 8px;">To add a project, simply clone or place your repository inside the <code>php/src/</code> directory:</p>
                <div class="code-box">git clone &lt;repo-url&gt; php/src/my-project</div>
                <p style="font-size: 13px;">Then refresh this page or use the <code>/check-php my-project</code> slash command.</p>
            </div>
        <?php else: ?>
            <div class="project-grid">
                <?php foreach ($projects as $proj): 
                    $safeProjName = htmlencode($proj['name']);
                    $initialUrl = 'http://' . htmlspecialchars($currentHost) . ':' . $currentPort . '/' . rawurlencode($proj['name']) . '/';
                    $initialWeadminUrl = 'http://' . htmlspecialchars($currentHost) . ':' . $currentPort . '/' . rawurlencode($proj['name']) . '/weadmin/';
                ?>
                    <div class="project-card" data-project="<?= $safeProjName ?>">
                        <div>
                            <div class="project-name"><?= htmlspecialchars($proj['name']) ?></div>
                            <div class="project-meta">
                                Modified: <?= date('Y-m-d H:i', $proj['mtime']) ?>
                                <?php if ($proj['hasWeadmin']): ?> &bull; <span style="color: var(--primary);">Has weadmin</span><?php endif; ?>
                            </div>
                        </div>

                        <div class="project-version-control">
                            <label class="control-label" for="select-version-<?= $safeProjName ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="4 17 10 11 4 5"></polyline>
                                    <line x1="12" y1="19" x2="20" y2="19"></line>
                                </svg>
                                <span>เลือก PHP Version:</span>
                            </label>
                            <select class="version-select" id="select-version-<?= $safeProjName ?>" data-project="<?= $safeProjName ?>" onchange="onProjectVersionChange(this)">
                                <option value="<?= $currentPort ?>"><?= htmlspecialchars($phpVersions[$currentPort]['name']) ?> (:<?= $currentPort ?>) - ปัจจุบัน</option>
                            </select>
                        </div>

                        <div class="btn-group">
                            <a href="<?= $initialUrl ?>" id="btn-website-<?= $safeProjName ?>" class="btn btn-primary btn-open-website" target="_blank">
                                Open Website &rarr;
                            </a>
                            <?php if ($proj['hasWeadmin']): ?>
                                <a href="<?= $initialWeadminUrl ?>" id="btn-weadmin-<?= $safeProjName ?>" class="btn btn-admin btn-open-weadmin" target="_blank">
                                    Open Admin Panel (/weadmin) &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <footer>
            <div class="footer-grid">
                <!-- Col 1: Brand & Workspace Info -->
                <div class="footer-col">
                    <div class="footer-brand">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        <span>PHP Multi-Version Hub</span>
                    </div>
                    <p class="footer-desc">
                        High-performance isolated development workspace supporting PHP 5.6 through 8.5 with shared workspace source and unified database services.
                    </p>
                    <div class="footer-status-pills">
                        <span class="f-pill"><span class="status-dot online"></span> PHP <?= htmlspecialchars($phpVersion) ?></span>
                        <span class="f-pill">Port <?= htmlspecialchars($currentPort) ?></span>
                        <span class="f-pill"><?= htmlspecialchars($hostEnv['badge']) ?></span>
                    </div>
                </div>

                <!-- Col 2: Infrastructure & Services -->
                <div class="footer-col">
                    <h4 class="footer-heading">Infrastructure &amp; Services</h4>
                    <ul class="footer-links">
                        <li>
                            <a href="http://<?= htmlspecialchars($currentHost) ?>:9000/" target="_blank" id="footer-link-portainer" class="<?= $portainerStatus ? '' : 'f-link-disabled' ?>" title="<?= $portainerStatus ? 'Open Portainer Web UI' : 'Portainer ปิดอยู่ (กรุณาเปิดผ่าน start.bat)' ?>">
                                <span class="status-dot <?= $portainerStatus ? 'online' : 'offline' ?>" id="footer-dot-portainer"></span>
                                <span class="f-icon">🐳</span> Portainer Web UI <span class="f-port">:9000</span>
                                <span class="badge-status <?= $portainerStatus ? 'online' : 'offline' ?>" style="font-size: 11px; padding: 1px 7px;" id="footer-tag-portainer">
                                    <?= $portainerStatus ? 'เปิดอยู่' : 'ปิดอยู่' ?>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="http://<?= htmlspecialchars($currentHost) ?>:8081/" target="_blank" id="footer-link-pma" class="<?= $pmaStatus ? '' : 'f-link-disabled' ?>" title="<?= $pmaStatus ? 'Open phpMyAdmin' : 'phpMyAdmin ปิดอยู่ (กรุณาเปิดผ่าน start.bat)' ?>">
                                <span class="status-dot <?= $pmaStatus ? 'online' : 'offline' ?>" id="footer-dot-pma"></span>
                                <span class="f-icon">🗄️</span> phpMyAdmin (local) <span class="f-port">:8081</span>
                                <span class="badge-status <?= $pmaStatus ? 'online' : 'offline' ?>" style="font-size: 11px; padding: 1px 7px;" id="footer-tag-pma">
                                    <?= $pmaStatus ? 'เปิดอยู่' : 'ปิดอยู่' ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-copy">
                    &copy; <?= date('Y') ?> <strong>PHP Multi-Version Workspace</strong> &bull; Zero-Touch Architecture &bull; Powered by Cherdsak Kh.
                </div>
                <div class="footer-meta">
                    <span>Repo: <code>cherdsak-kh/php-workspace</code></span>
                </div>
            </div>
        </footer>
    </div>

    <script>
        const phpVersions = <?= json_encode($phpVersions) ?>;
        const currentPort = <?= json_encode((string)$currentPort) ?>;
        const host = window.location.hostname || 'localhost';
        const liveStatus = {};

        function copyText(text, btn) {
            if (!text) return;
            navigator.clipboard.writeText(text).then(function() {
                var old = btn.innerText;
                btn.innerText = 'Copied!';
                btn.style.background = '#10b981';
                btn.style.color = '#fff';
                setTimeout(function() {
                    btn.innerText = old;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 1500);
            });
        }

        // Check single PHP version container status
        async function checkSinglePhp(port, info) {
            // Current active runtime is always online
            if (port === currentPort) {
                applyStatus(port, true, '0ms', true);
                return;
            }

            const startTime = performance.now();
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 1200);

            try {
                const response = await fetch(`http://${host}:${port}/?ping=1`, {
                    mode: 'cors',
                    cache: 'no-store',
                    signal: controller.signal
                });
                clearTimeout(timeoutId);

                if (response.ok) {
                    const latency = Math.round(performance.now() - startTime);
                    applyStatus(port, true, `${latency}ms`, false);
                } else {
                    applyStatus(port, false, null, false);
                }
            } catch (err) {
                // Fallback attempt with no-cors probe
                try {
                    const fbController = new AbortController();
                    const fbTimeout = setTimeout(() => fbController.abort(), 800);
                    await fetch(`http://${host}:${port}/?ping=1`, {
                        mode: 'no-cors',
                        cache: 'no-store',
                        signal: fbController.signal
                    });
                    clearTimeout(fbTimeout);
                    const latency = Math.round(performance.now() - startTime);
                    applyStatus(port, true, `${latency}ms`, false);
                } catch (e) {
                    applyStatus(port, false, null, false);
                }
            }
        }

        function updateSummaryCounters() {
            let online = 0;
            let offline = 0;
            for (const p of Object.keys(phpVersions)) {
                if (liveStatus[p] === true) {
                    online++;
                } else if (liveStatus[p] === false) {
                    offline++;
                }
            }
            const onlineText = document.getElementById('sum-online-text');
            const offlineText = document.getElementById('sum-offline-text');
            if (onlineText) onlineText.innerText = `${online} เปิดอยู่`;
            if (offlineText) offlineText.innerText = `${offline} ปิดอยู่`;
        }

        function applyStatus(port, isOnline, latency, isCurrent) {
            liveStatus[port] = isOnline;

            const navDot = document.getElementById(`nav-dot-${port}`);
            const navLink = document.getElementById(`nav-link-${port}`);
            const rowDot = document.getElementById(`row-dot-${port}`);
            const cardBadge = document.getElementById(`card-badge-${port}`);
            const info = phpVersions[port] || { name: `PHP (${port})` };

            if (isOnline) {
                // Top Nav Dot & Link (Clickable)
                if (navDot) navDot.className = 'status-dot online';
                if (navLink) {
                    navLink.classList.remove('is-offline');
                    navLink.href = `http://${host}:${port}/`;
                    navLink.onclick = null;
                    navLink.title = isCurrent 
                        ? `${info.name} (:${port}) - กำลังใช้งานอยู่` 
                        : `${info.name} (:${port}) - เปิดอยู่ (คลิกเพื่อสลับไปยังเวอร์ชันนี้)`;
                }

                // Accordion Row Badge
                if (rowDot) rowDot.className = 'status-dot online';
                if (cardBadge) {
                    const latText = latency ? ` &bull; ${latency}` : '';
                    cardBadge.innerHTML = `
                        <span class="badge-status online">
                            <span class="status-dot online"></span>
                            <span>เปิดอยู่${latText}</span>
                        </span>
                    `;
                }
            } else {
                // Top Nav Dot & Link (Disabled / Unclickable when offline)
                if (navDot) navDot.className = 'status-dot offline';
                if (navLink) {
                    navLink.classList.add('is-offline');
                    navLink.removeAttribute('href');
                    navLink.onclick = function(e) { e.preventDefault(); return false; };
                    navLink.title = `${info.name} (:${port}) - ปิดอยู่ (กรุณาเปิดผ่าน start.bat)`;
                }

                // Accordion Row Badge
                if (rowDot) rowDot.className = 'status-dot offline';
                if (cardBadge) {
                    cardBadge.innerHTML = `
                        <span class="badge-status offline">
                            <span class="status-dot offline"></span>
                            <span>ปิดอยู่</span>
                        </span>
                    `;
                }
            }

            updateSummaryCounters();
            scheduleUpdateSelects();
        }

        let updateSelectsTimeout = null;
        function scheduleUpdateSelects() {
            clearTimeout(updateSelectsTimeout);
            updateSelectsTimeout = setTimeout(updateAllProjectVersionSelects, 60);
        }

        function getOnlinePorts() {
            const ports = [];
            for (const p of Object.keys(phpVersions)) {
                if (p === currentPort || liveStatus[p] === true) {
                    ports.push(p);
                }
            }
            return ports;
        }

        function updateCardButtons(card, projName, port) {
            if (!card) return;
            const btnWebsite = card.querySelector('.btn-open-website');
            const btnWeadmin = card.querySelector('.btn-open-weadmin');

            if (!port) {
                if (btnWebsite) {
                    btnWebsite.removeAttribute('href');
                    btnWebsite.style.opacity = '0.5';
                    btnWebsite.style.pointerEvents = 'none';
                }
                if (btnWeadmin) {
                    btnWeadmin.removeAttribute('href');
                    btnWeadmin.style.opacity = '0.5';
                    btnWeadmin.style.pointerEvents = 'none';
                }
                return;
            }

            const targetUrl = `http://${host}:${port}/${encodeURIComponent(projName)}/`;
            const targetWeadmin = `http://${host}:${port}/${encodeURIComponent(projName)}/weadmin/`;

            if (btnWebsite) {
                btnWebsite.href = targetUrl;
                btnWebsite.style.opacity = '';
                btnWebsite.style.pointerEvents = '';
            }
            if (btnWeadmin) {
                btnWeadmin.href = targetWeadmin;
                btnWeadmin.style.opacity = '';
                btnWeadmin.style.pointerEvents = '';
            }
        }

        function updateAllProjectVersionSelects() {
            const cards = document.querySelectorAll('.project-card');
            const onlinePorts = getOnlinePorts();

            cards.forEach(card => {
                const select = card.querySelector('.version-select');
                if (!select) return;

                const projName = select.dataset.project;
                const prevSelected = select.value || currentPort;

                // Repopulate options with only online versions
                select.innerHTML = '';

                if (onlinePorts.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'ไม่มีเวอร์ชันที่เปิดอยู่';
                    opt.disabled = true;
                    opt.selected = true;
                    select.appendChild(opt);
                    updateCardButtons(card, projName, null);
                    return;
                }

                let isSelectedStillOnline = false;
                onlinePorts.forEach(p => {
                    const info = phpVersions[p];
                    const opt = document.createElement('option');
                    opt.value = p;
                    const isCur = (p === currentPort);
                    opt.textContent = `${info.name} (:${p})${isCur ? ' - ปัจจุบัน' : ''}`;
                    if (p === prevSelected) {
                        opt.selected = true;
                        isSelectedStillOnline = true;
                    }
                    select.appendChild(opt);
                });

                if (!isSelectedStillOnline) {
                    const fallback = onlinePorts.includes(currentPort) ? currentPort : onlinePorts[0];
                    select.value = fallback;
                }

                updateCardButtons(card, projName, select.value);
            });
        }

        function onProjectVersionChange(select) {
            const card = select.closest('.project-card');
            const projName = select.dataset.project;
            updateCardButtons(card, projName, select.value);
        }

        // Check Infrastructure Services (Portainer :9000, phpMyAdmin :8081)
        async function checkInfrastructureStatus() {
            // Portainer
            try {
                const c1 = new AbortController();
                const t1 = setTimeout(() => c1.abort(), 1200);
                await fetch(`http://${host}:9000/`, { mode: 'no-cors', cache: 'no-store', signal: c1.signal });
                clearTimeout(t1);
                applyToolStatus('portainer', true);
            } catch (e) {
                applyToolStatus('portainer', false);
            }

            // phpMyAdmin
            try {
                const c2 = new AbortController();
                const t2 = setTimeout(() => c2.abort(), 1200);
                await fetch(`http://${host}:8081/`, { mode: 'no-cors', cache: 'no-store', signal: c2.signal });
                clearTimeout(t2);
                applyToolStatus('pma', true);
            } catch (e) {
                applyToolStatus('pma', false);
            }
        }

        function applyToolStatus(tool, isOnline) {
            const dot = document.getElementById(`footer-dot-${tool}`);
            const tag = document.getElementById(`footer-tag-${tool}`);
            const fLink = document.getElementById(`footer-link-${tool}`);

            if (dot) dot.className = `status-dot ${isOnline ? 'online' : 'offline'}`;
            if (tag) {
                tag.className = `badge-status ${isOnline ? 'online' : 'offline'}`;
                tag.innerText = isOnline ? 'เปิดอยู่' : 'ปิดอยู่';
            }

            const toolName = (tool === 'portainer') ? 'Portainer' : 'phpMyAdmin';
            const toolPort = (tool === 'portainer') ? '9000' : '8081';

            if (fLink) {
                if (isOnline) {
                    fLink.classList.remove('f-link-disabled');
                    fLink.href = `http://${host}:${toolPort}/`;
                    fLink.onclick = null;
                    fLink.title = `${toolName} (:${toolPort}) - เปิดอยู่ (คลิกเพื่อเปิดเว็บ)`;
                } else {
                    fLink.classList.add('f-link-disabled');
                    fLink.removeAttribute('href');
                    fLink.onclick = function(e) { e.preventDefault(); return false; };
                    fLink.title = `${toolName} (:${toolPort}) - ปิดอยู่ (กรุณาเปิดผ่าน start.bat)`;
                }
            }

            // Also synchronize Portainer Header and Card buttons
            if (tool === 'portainer') {
                const headerBtn = document.getElementById('header-btn-portainer');
                const cardBtn = document.getElementById('card-btn-portainer');

                if (headerBtn) {
                    if (isOnline) {
                        headerBtn.classList.remove('btn-disabled');
                        headerBtn.href = `http://${host}:9000/`;
                        headerBtn.onclick = null;
                        headerBtn.title = 'Manage containers, view logs, inspect stats';
                    } else {
                        headerBtn.classList.add('btn-disabled');
                        headerBtn.removeAttribute('href');
                        headerBtn.onclick = function(e) { e.preventDefault(); return false; };
                        headerBtn.title = 'Portainer ปิดอยู่ (กรุณาเปิดผ่าน start.bat)';
                    }
                }

                if (cardBtn) {
                    if (isOnline) {
                        cardBtn.classList.remove('btn-disabled');
                        cardBtn.href = `http://${host}:9000/`;
                        cardBtn.onclick = null;
                        cardBtn.innerHTML = 'Open Portainer Dashboard &rarr;';
                        cardBtn.title = 'Open Portainer Dashboard';
                    } else {
                        cardBtn.classList.add('btn-disabled');
                        cardBtn.removeAttribute('href');
                        cardBtn.onclick = function(e) { e.preventDefault(); return false; };
                        cardBtn.innerHTML = 'Portainer Offline &times;';
                        cardBtn.title = 'Portainer ปิดอยู่ (กรุณาเปิดผ่าน start.bat)';
                    }
                }
            }
        }

        // Check all versions
        function checkAllPhpVersions(showLoading = false) {
            const btn = document.getElementById('btn-refresh-status');
            if (btn && showLoading) {
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.innerHTML = '<span>กำลังตรวจ...</span>';
            }

            checkInfrastructureStatus();

            const promises = Object.keys(phpVersions).map(port => {
                return checkSinglePhp(port, phpVersions[port]);
            });

            Promise.allSettled(promises).then(() => {
                updateSummaryCounters();
                updateAllProjectVersionSelects();
                if (btn && showLoading) {
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.innerHTML = `
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                            </svg>
                            <span>รีเฟรชสถานะ</span>
                        `;
                    }, 400);
                }
            });
        }

        // Run immediately on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateAllProjectVersionSelects();
            checkAllPhpVersions(false);
            checkInfrastructureStatus();
        });
    </script>
</body>
</html>
<?php
function htmlencode($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
