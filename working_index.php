<?php
// Hotel Channel Manager - Working Version
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Channel Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        .btn.success { background: #28a745; }
        .btn.warning { background: #ffc107; color: black; }
        .btn.danger { background: #dc3545; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .status.ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h3 { color: #333; margin-bottom: 15px; }
        .card p { color: #666; margin-bottom: 15px; }
        .icon { font-size: 2rem; margin-right: 10px; }
        .step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
        .step h4 { color: #007bff; margin-bottom: 10px; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; border: 1px solid #dee2e6; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .alert.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏨 Hotel Channel Manager</h1>
        
        <div class="alert info">
            <strong>Welcome!</strong> This is a complete Hotel Channel Manager system with OTA integration. Follow the steps below to get started.
        </div>
        
        <div class="section">
            <h2>🚀 Quick Start</h2>
            <div class="grid">
                <div class="card">
                    <h3>📋 Step 1: System Check</h3>
                    <p>Check if your server meets all requirements and test basic functionality.</p>
                    <a href="system_check.php" class="btn success">Run System Check</a>
                </div>
                
                <div class="card">
                    <h3>🗄️ Step 2: Database Setup</h3>
                    <p>Create database and import the required tables for the application.</p>
                    <a href="database_setup.php" class="btn warning">Setup Database</a>
                </div>
                
                <div class="card">
                    <h3>🔐 Step 3: Login System</h3>
                    <p>Access the admin panel to manage your hotels and OTA connections.</p>
                    <a href="login.php" class="btn">Admin Login</a>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>📖 Installation Steps</h2>
            
            <div class="step">
                <h4>1. System Requirements</h4>
                <p>Make sure your server has:</p>
                <ul>
                    <li>PHP 7.4 or higher</li>
                    <li>MySQL 5.7 or higher</li>
                    <li>PHP extensions: curl, pdo_mysql, json, mbstring</li>
                </ul>
            </div>
            
            <div class="step">
                <h4>2. Database Creation</h4>
                <div class="code">
                    CREATE DATABASE hotel_channel_manager;<br>
                    USE hotel_channel_manager;
                </div>
            </div>
            
            <div class="step">
                <h4>3. Configuration</h4>
                <p>Edit the database credentials in the configuration file as needed.</p>
            </div>
            
            <div class="step">
                <h4>4. Access Admin Panel</h4>
                <p>Default login credentials:</p>
                <div class="code">
                    Username: admin<br>
                    Password: admin123
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>🔧 Tools & Utilities</h2>
            <div class="grid">
                <div class="card">
                    <h3>🔍 System Check</h3>
                    <p>Verify server configuration and requirements</p>
                    <a href="system_check.php" class="btn">Check System</a>
                </div>
                
                <div class="card">
                    <h3>🗄️ Database Manager</h3>
                    <p>Setup and manage database tables</p>
                    <a href="database_setup.php" class="btn">Manage Database</a>
                </div>
                
                <div class="card">
                    <h3>🧪 Test Suite</h3>
                    <p>Run comprehensive system tests</p>
                    <a href="test_suite.php" class="btn">Run Tests</a>
                </div>
                
                <div class="card">
                    <h3>📊 Dashboard</h3>
                    <p>Access the main admin dashboard</p>
                    <a href="dashboard.php" class="btn">View Dashboard</a>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>🌟 Features</h2>
            <div class="grid">
                <div class="card">
                    <h3>🔗 OTA Integration</h3>
                    <p>Connect with Booking.com, Agoda, Expedia and other major OTAs</p>
                </div>
                
                <div class="card">
                    <h3>📅 Inventory Management</h3>
                    <p>Real-time room availability and rate synchronization</p>
                </div>
                
                <div class="card">
                    <h3>💼 Reservation Management</h3>
                    <p>Centralized booking management from all channels</p>
                </div>
                
                <div class="card">
                    <h3>📈 Analytics & Reporting</h3>
                    <p>Comprehensive reports and performance analytics</p>
                </div>
            </div>
        </div>
        
        <div class="alert success">
            <strong>Ready to Start?</strong> Click on "Run System Check" to verify your server configuration, then proceed with the database setup.
        </div>
    </div>
</body>
</html>