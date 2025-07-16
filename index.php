<?php
// Hotel Channel Manager - Main Entry Point
echo "<h1>🏨 Hotel Channel Manager</h1>";
echo "<p>Welcome to the Hotel Channel Manager system.</p>";

echo "<h2>🚀 Quick Start Options</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>1. Setup & Diagnosis</h3>";
echo "<p><a href='setup.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🔧 Run Setup</a> - Creates directories and checks system</p>";
echo "<p><a href='debug.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🔍 Debug Info</a> - Detailed system information</p>";
echo "<p><a href='test.php' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🧪 System Test</a> - Basic functionality test</p>";
echo "</div>";

echo "<div style='background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>2. Login Options</h3>";
echo "<p><a href='admin/simple_login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🔐 Simple Login</a> - Basic login system (admin/admin123)</p>";
echo "<p><a href='admin/login.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🏨 Full System</a> - Complete hotel management system</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>⚠️ Getting HTTP 500 Error?</h3>";
echo "<ol>";
echo "<li>Start with <strong>setup.php</strong> to check your system</li>";
echo "<li>Try <strong>test.php</strong> for basic PHP testing</li>";
echo "<li>Use <strong>debug.php</strong> for detailed error information</li>";
echo "<li>Try <strong>simple_login.php</strong> before the full system</li>";
echo "</ol>";
echo "</div>";
?>
