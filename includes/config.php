<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'calendar_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Open Calendar System');
define('APP_VERSION', '1.0.0');
// define('TIMEZONE', 'UTC');
define('TIMEZONE', 'Asia/Kuala_Lumpur');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>