<?php
/**
 * ISP Billing System Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'billing');
define('DB_USER', 'billing');
define('DB_PASS', 'Billing123');

// FreeRADIUS Database Configuration
define('RADIUS_DB_HOST', 'localhost');
define('RADIUS_DB_NAME', 'radius');
define('RADIUS_DB_USER', 'billing');
define('RADIUS_DB_PASS', 'Billing123');

// Application Settings
define('APP_NAME', 'ISP Billing System');
define('APP_URL', 'http://localhost/isp-billing');
define('APP_ROOT', __DIR__);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
