<?php
// Simple Configuration File for Hotel Channel Manager

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_channel_manager');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Hotel Channel Manager');
define('APP_VERSION', '1.0.0');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// Test database connection
function testDatabaseConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>