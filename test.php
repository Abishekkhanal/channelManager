<?php
echo "<h1>Simple PHP Test</h1>";
echo "<p>If you can see this, PHP is working!</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test database connection
$host = 'localhost';
$dbname = 'hotel_channel_manager';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "<p>✅ Database connection successful</p>";
} catch (PDOException $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

phpinfo();
?>