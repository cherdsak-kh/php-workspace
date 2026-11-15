<?php
/**
 * PHP Multi-Version Hub - Landing Dashboard
 * Displays workspace health, active PHP runtime, host & Docker environment detection,
 * Portainer credentials/token, and detected projects in php/src/
 */

$currentPort = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80';
$currentHost = isset($_SERVER['HTTP_HOST']) ? explode(':', $_SERVER['HTTP_HOST'])[0] : 'localhost';
$phpVersion = PHP_VERSION;

// Port to PHP version mapping
$phpVersions = [
    '5640' => ['version' => '5.6.40', 'name' => 'PHP 5.6', 'desc' => 'Legacy projects (mysql ext)'],
    '7433' => ['version' => '7.4.33', 'name' => 'PHP 7.4', 'desc' => 'Transitional (Smarty 3/4)'],
    '8131' => ['version' => '8.1.34', 'name' => 'PHP 8.1', 'desc' => 'Early strict types'],
    '8235' => ['version' => '8.2.35', 'name' => 'PHP 8.2', 'desc' => 'Modern active'],
    '8336' => ['version' => '8.3.36', 'name' => 'PHP 8.3', 'desc' => 'Standard modern default'],
    '8427' => ['version' => '8.4.27', 'name' => 'PHP 8.4', 'desc' => 'Latest stable'],
    '8512' => ['version' => '8.5.12', 'name' => 'PHP 8.5', 'desc' => 'Next-gen experimental'],
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
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
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
        .version-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            background: var(--surface);
            padding: 8px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .version-nav a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .version-nav a:hover {
            color: #fff;
            background: var(--surface-hover);
        }
        .version-nav a.active {
            background: var(--primary);
            color: #0f172a;
            font-weight: 700;
        }
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
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.online { background: var(--success); box-shadow: 0 0 10px var(--success); }
        .status-dot.offline { background: var(--danger); box-shadow: 0 0 10px var(--danger); }
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
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .section-header h2 { font-size: 18px; font-weight: 600; }
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
            margin-bottom: 16px;
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
        }
        .btn-primary { background: #0284c7; color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary { background: var(--surface-hover); color: var(--text); }
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
        footer {
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }
        footer a { color: var(--primary); text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
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
                <a href="http://<?= htmlspecialchars($currentHost) ?>:9000/" target="_blank" class="btn-portainer" title="Manage containers, view logs, inspect stats">
                    🐳 Container Manager (Portainer)
                </a>
                <span class="badge-php">Current: PHP <?= htmlspecialchars($phpVersion) ?> (Port <?= htmlspecialchars($currentPort) ?>)</span>
            </div>
        </header>

        <!-- PHP Version Switcher -->
        <div class="version-nav">
            <?php foreach ($phpVersions as $p => $info): ?>
                <a href="http://<?= htmlspecialchars($currentHost) ?>:<?= $p ?>/" class="<?= ($currentPort == $p) ? 'active' : '' ?>" title="<?= htmlspecialchars($info['desc']) ?>">
                    <?= htmlspecialchars($info['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

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
                    <a href="http://<?= htmlspecialchars($currentHost) ?>:9000/" target="_blank" class="btn btn-secondary" style="padding: 6px 10px; font-size: 12px; width: 100%;">
                        Open Portainer Dashboard &rarr;
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
                            <div style="font-size: 12px; color: var(--text-muted);">Saved in <code>portainer-credentials.txt</code> &bull; No docker exec needed!</div>
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
                    $subfolderUrl = '/' . urlencode($proj['name']) . '/';
                    $domainUrl = 'http://' . htmlencode($proj['name']) . '.localhost:' . $currentPort . '/';
                    $subfolderWeadmin = '/' . urlencode($proj['name']) . '/weadmin/';
                    $domainWeadmin = 'http://' . htmlencode($proj['name']) . '.localhost:' . $currentPort . '/weadmin/';
                ?>
                    <div class="project-card">
                        <div>
                            <div class="project-name"><?= htmlspecialchars($proj['name']) ?></div>
                            <div class="project-meta">
                                Modified: <?= date('Y-m-d H:i', $proj['mtime']) ?>
                                <?php if ($proj['hasWeadmin']): ?> &bull; <span style="color: var(--primary);">Has weadmin</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="btn-group">
                            <a href="<?= $subfolderUrl ?>" class="btn btn-primary" target="_blank">
                                Open Subfolder Mode &rarr;
                            </a>
                            <a href="<?= $domainUrl ?>" class="btn btn-secondary" target="_blank" title="Dynamic root mapping (*.localhost)">
                                Open Root Domain Mode &rarr;
                            </a>
                            <?php if ($proj['hasWeadmin']): ?>
                                <a href="<?= $subfolderWeadmin ?>" class="btn btn-admin" target="_blank">
                                    Open Admin Panel (/weadmin) &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <footer>
            <p>PHP Multi-Version Workspace &bull; Antigravity AI Agent Hub</p>
            <p style="margin-top: 4px;">Run <code>/check-php &lt;project&gt; [version]</code> to test compatibility and diagnose local readiness.</p>
        </footer>
    </div>

    <script>
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
    </script>
</body>
</html>
<?php
function htmlencode($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
