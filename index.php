<?php
// Hotel Channel Manager - Main Entry Point
session_start();

require_once 'config/config.php';

// Auto-redirect to admin interface
header('Location: /admin/dashboard.php');
exit;
?>
