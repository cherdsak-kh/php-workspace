<?php
/**
 * PHP Workspace Compatibility Shim
 * Loaded automatically via auto_prepend_file in all PHP versions
 */

// 1. Prevent fatal exceptions on legacy MySQLi queries in PHP 8.1+
if (function_exists('mysqli_report') && defined('MYSQLI_REPORT_OFF')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}
