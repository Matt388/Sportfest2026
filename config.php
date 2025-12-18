<?php
// Configuration file for Sportfest Manager

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_PATH', __DIR__ . '/sportfest.db');

// Session configuration
define('SESSION_NAME', 'sportfest_session');
define('SECRET_KEY', 'sportfest-secret-key-change-in-production'); // Change this in production!

// Application settings
define('BASE_URL', '/Sportfest');
define('DEBUG', true);

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>
