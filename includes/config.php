<?php
/**
 * TailorMate - Configuration
 * Central configuration file for database and app settings.
 */

// Database Configuration
// These values are auto-detected in Docker via environment variables.
// Override them here only if running outside Docker.
define('DB_HOST', getenv('DB_HOST') ?: 'mariadb');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'tailormate_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'tailormate');

// App Configuration
define('APP_NAME', 'TailorMate');
define('SESSION_LIFETIME', 3600); // 1 hour

// Error Reporting - disable display in production
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
