<?php
session_start();
require_once '../simple_config.php';

// Check if logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    redirect('simple_login.php');
}

// Test database connection
$db_status = testDatabaseConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; color: #333; }
        .logout { float: right; }
        .logout a { color: #007bff; text-decoration: none; }
        .status-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-card h2 { margin-top: 0; color: #333; }
        .status { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏨 <?php echo APP_NAME; ?> Dashboard</h1>
        <div class="logout">
            Welcome, <?php echo $_SESSION['username']; ?> | 
            <a href="logout.php">Logout</a>
        </div>
        <div style="clear: both;"></div>
    </div>
    
    <div class="status-card">
        <h2>System Status</h2>
        
        <div class="status <?php echo $db_status ? 'success' : 'error'; ?>">
            Database Connection: <?php echo $db_status ? '✅ Connected' : '❌ Failed'; ?>
        </div>
        
        <div class="status success">
            PHP Version: <?php echo phpversion(); ?>
        </div>
        
        <div class="status success">
            Application Status: ✅ Running
        </div>
    </div>
    
    <div class="status-card">
        <h2>Quick Actions</h2>
        
        <a href="../debug.php" class="btn">🔍 Debug Information</a>
        <a href="../test.php" class="btn">🧪 System Test</a>
        <a href="login.php" class="btn">🔐 Full Login System</a>
    </div>
    
    <div class="status-card">
        <h2>Installation Status</h2>
        
        <h3>Required Files:</h3>
        <ul>
            <li>config/config.php: <?php echo file_exists('../config/config.php') ? '✅' : '❌'; ?></li>
            <li>config/database.sql: <?php echo file_exists('../config/database.sql') ? '✅' : '❌'; ?></li>
            <li>classes/Database.php: <?php echo file_exists('../classes/Database.php') ? '✅' : '❌'; ?></li>
            <li>classes/Auth.php: <?php echo file_exists('../classes/Auth.php') ? '✅' : '❌'; ?></li>
        </ul>
        
        <h3>Required Directories:</h3>
        <ul>
            <li>logs/: <?php echo is_dir('../logs') ? '✅' : '❌'; ?></li>
            <li>uploads/: <?php echo is_dir('../uploads') ? '✅' : '❌'; ?></li>
        </ul>
        
        <h3>Database Tables:</h3>
        <?php if ($db_status): ?>
            <?php
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $tables = ['users', 'hotels', 'ota_channels', 'reservations', 'room_types', 'room_inventory'];
                echo "<ul>";
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    $exists = $stmt->rowCount() > 0;
                    echo "<li>$table: " . ($exists ? '✅' : '❌') . "</li>";
                }
                echo "</ul>";
            } catch (Exception $e) {
                echo "<p>Error checking tables: " . $e->getMessage() . "</p>";
            }
            ?>
        <?php else: ?>
            <p>❌ Cannot check tables - database connection failed</p>
        <?php endif; ?>
    </div>
    
    <div class="status-card">
        <h2>Next Steps</h2>
        <ol>
            <li>If database connection failed, check your credentials in simple_config.php</li>
            <li>If tables are missing, import config/database.sql</li>
            <li>Once everything is working, try the full login system</li>
            <li>Create missing directories if they don't exist</li>
        </ol>
    </div>
</body>
</html>