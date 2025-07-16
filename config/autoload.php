<?php
// Autoloader for Hotel Channel Manager

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';
    $classFile = $baseDir . 'classes/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Include common functions
require_once __DIR__ . '/../classes/functions.php';
?>