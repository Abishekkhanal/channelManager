<?php
session_start();
require_once '../config/config.php';
Auth::getInstance()->logout();
redirect('login.php');
?>
