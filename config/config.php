<?php
// Hotel Channel Manager Configuration

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_channel_manager');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Hotel Channel Manager');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');
define('APP_TIMEZONE', 'UTC');

// Security Configuration
define('SECRET_KEY', 'your-secret-key-change-this-in-production');
define('JWT_SECRET', 'your-jwt-secret-key');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 300); // 5 minutes

// File Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_DIR', 'uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// API Configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('API_TIMEOUT', 30); // seconds
define('API_RETRY_ATTEMPTS', 3);

// OTA Configuration
define('OTA_SYNC_INTERVAL', 300); // 5 minutes
define('OTA_TIMEOUT', 60); // seconds
define('OTA_RETRY_ATTEMPTS', 3);

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@yourhotel.com');
define('SMTP_FROM_NAME', 'Hotel Channel Manager');

// Logging Configuration
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', 'logs/app.log');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour

// Multi-language Configuration
define('DEFAULT_LANGUAGE', 'en');
define('SUPPORTED_LANGUAGES', ['en', 'es', 'fr', 'de', 'it', 'pt']);

// Date & Time Format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Pagination
define('ITEMS_PER_PAGE', 25);
define('MAX_ITEMS_PER_PAGE', 100);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting
if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Include autoloader
require_once __DIR__ . '/autoload.php';
?>