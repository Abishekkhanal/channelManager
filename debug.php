<?php
// Debug script to identify HTTP 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Hotel Channel Manager Debug</h1>";

echo "<h2>1. PHP Version Check</h2>";
echo "PHP Version: " . phpversion() . "<br>";

echo "<h2>2. Required Extensions Check</h2>";
$required_extensions = ['curl', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "✅ Loaded" : "❌ Missing";
    echo "$ext: $status<br>";
}

echo "<h2>3. File Permissions Check</h2>";
$check_paths = [
    '.',
    'config',
    'classes',
    'admin',
    'api',
    'lang'
];

foreach ($check_paths as $path) {
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "Directory $path: $perms<br>";
    } else {
        echo "Directory $path: ❌ Not found<br>";
    }
}

echo "<h2>4. Key Files Check</h2>";
$key_files = [
    'config/config.php',
    'config/autoload.php',
    'classes/Database.php',
    'classes/Auth.php',
    'classes/functions.php',
    'admin/login.php',
    'index.php'
];

foreach ($key_files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "File $file: ✅ Exists ($size bytes)<br>";
    } else {
        echo "File $file: ❌ Missing<br>";
    }
}

echo "<h2>5. Configuration Test</h2>";
if (file_exists('config/config.php')) {
    try {
        include 'config/config.php';
        echo "Config file: ✅ Loaded successfully<br>";
        echo "App Name: " . (defined('APP_NAME') ? APP_NAME : 'Not defined') . "<br>";
        echo "DB Host: " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "<br>";
        echo "DB Name: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "<br>";
    } catch (Exception $e) {
        echo "Config file: ❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Config file: ❌ Not found<br>";
}

echo "<h2>6. Database Connection Test</h2>";
if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        echo "Database connection: ✅ Success<br>";
        
        // Test if tables exist
        $tables = ['users', 'hotels', 'ota_channels', 'reservations'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            $status = $exists ? "✅ Exists" : "❌ Missing";
            echo "Table $table: $status<br>";
        }
    } catch (Exception $e) {
        echo "Database connection: ❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Database connection: ❌ Configuration missing<br>";
}

echo "<h2>7. Write Permissions Test</h2>";
$write_dirs = ['logs', 'uploads'];
foreach ($write_dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    
    if (is_writable($dir)) {
        echo "Directory $dir: ✅ Writable<br>";
    } else {
        echo "Directory $dir: ❌ Not writable<br>";
    }
}

echo "<h2>8. PHP Error Log Check</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "Error log location: $error_log<br>";
    $errors = file_get_contents($error_log);
    $recent_errors = array_slice(explode("\n", $errors), -10);
    echo "Recent errors:<br>";
    foreach ($recent_errors as $error) {
        if (trim($error)) {
            echo htmlspecialchars($error) . "<br>";
        }
    }
} else {
    echo "Error log: Not found or not configured<br>";
}

echo "<h2>9. Test Basic PHP</h2>";
try {
    echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
    echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
    echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
} catch (Exception $e) {
    echo "Basic PHP test: ❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>10. Memory and Limits</h2>";
echo "Memory limit: " . ini_get('memory_limit') . "<br>";
echo "Max execution time: " . ini_get('max_execution_time') . "<br>";
echo "Upload max filesize: " . ini_get('upload_max_filesize') . "<br>";

echo "<hr>";
echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Check the results above for any ❌ errors</li>";
echo "<li>Fix any missing extensions or permissions</li>";
echo "<li>If database connection fails, check your credentials</li>";
echo "<li>After fixing issues, delete this debug.php file</li>";
echo "</ol>";
?>