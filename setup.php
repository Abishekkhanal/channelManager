<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🏨 Hotel Channel Manager Setup</h1>";

// Create directories
$directories = ['logs', 'uploads'];
echo "<h2>Creating Directories</h2>";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir<br>";
        } else {
            echo "❌ Failed to create directory: $dir<br>";
        }
    } else {
        echo "✅ Directory already exists: $dir<br>";
    }
}

// Check file permissions
echo "<h2>Checking Permissions</h2>";
foreach ($directories as $dir) {
    if (is_writable($dir)) {
        echo "✅ Directory $dir is writable<br>";
    } else {
        echo "❌ Directory $dir is not writable<br>";
    }
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
$host = 'localhost';
$dbname = 'hotel_channel_manager';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "✅ Database connection successful<br>";
    
    // Check if tables exist
    $tables = ['users', 'hotels', 'ota_channels', 'reservations', 'room_types', 'room_inventory', 'sync_logs', 'settings', 'rate_plans', 'hotel_ota_mappings'];
    echo "<h3>Database Tables Status:</h3>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo ($exists ? '✅' : '❌') . " Table: $table<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "<h3>Database Setup Instructions:</h3>";
    echo "<ol>";
    echo "<li>Create database: <code>CREATE DATABASE hotel_channel_manager;</code></li>";
    echo "<li>Import schema: <code>mysql -u root -p hotel_channel_manager < config/database.sql</code></li>";
    echo "<li>Update database credentials in simple_config.php if needed</li>";
    echo "</ol>";
}

// Check PHP extensions
echo "<h2>PHP Extensions Check</h2>";
$required_extensions = ['curl', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "✅ Loaded" : "❌ Missing";
    echo "$ext: $status<br>";
}

echo "<h2>Quick Start</h2>";
echo "<ol>";
echo "<li><strong>Test Basic Setup:</strong> <a href='test.php'>test.php</a></li>";
echo "<li><strong>Simple Login:</strong> <a href='admin/simple_login.php'>admin/simple_login.php</a> (admin/admin123)</li>";
echo "<li><strong>Debug Info:</strong> <a href='debug.php'>debug.php</a></li>";
echo "<li><strong>Full System:</strong> <a href='admin/login.php'>admin/login.php</a> (once everything is working)</li>";
echo "</ol>";

echo "<h2>File Structure Check</h2>";
$required_files = [
    'config/config.php',
    'config/database.sql',
    'classes/Database.php',
    'classes/Auth.php',
    'classes/functions.php',
    'admin/login.php',
    'admin/dashboard.php'
];

foreach ($required_files as $file) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    echo ($exists ? '✅' : '❌') . " $file";
    if ($exists) {
        echo " (" . number_format($size) . " bytes)";
    }
    echo "<br>";
}

echo "<h2>Next Steps</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<ol>";
echo "<li>If you see database connection errors, create the database and import the schema</li>";
echo "<li>Try the simple login system first: <a href='admin/simple_login.php'>admin/simple_login.php</a></li>";
echo "<li>Once that works, try the full system: <a href='admin/login.php'>admin/login.php</a></li>";
echo "<li>Check the debug page for detailed information: <a href='debug.php'>debug.php</a></li>";
echo "</ol>";
echo "</div>";

echo "<h2>Troubleshooting</h2>";
echo "<div style='background: #fff8dc; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Common Issues:</h3>";
echo "<ul>";
echo "<li><strong>HTTP 500 Error:</strong> Check PHP error logs, missing files, or database connection issues</li>";
echo "<li><strong>Database Connection Failed:</strong> Verify database exists, credentials are correct</li>";
echo "<li><strong>Permission Denied:</strong> Check file permissions on logs/ and uploads/ directories</li>";
echo "<li><strong>Missing Extensions:</strong> Install required PHP extensions</li>";
echo "</ul>";
echo "</div>";
?>