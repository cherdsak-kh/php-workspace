<?php
/**
 * PHP Workspace Compatibility Shim
 * Loaded automatically via auto_prepend_file in all PHP versions
 */

// 1. Prevent fatal exceptions on legacy MySQLi queries in PHP 8.1+
if (function_exists('mysqli_report') && defined('MYSQLI_REPORT_OFF')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// 2. Multi-PHP Hub Environment Compatibility
// Legacy projects often check if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'localhost:8080')
// to switch to their local DEV configuration (DB credentials, base URL, etc.).
// When accessed via multi-port containers (e.g. localhost:7433, localhost:8336) or subdomains (*.localhost),
// we normalize HTTP_HOST to 'localhost:8080' so DEV environment is automatically selected.
if (isset($_SERVER['HTTP_HOST']) && preg_match('/localhost/i', $_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST_ORIGINAL'] = $_SERVER['HTTP_HOST'];
    $_SERVER['HTTP_HOST'] = 'localhost:8080';
}
