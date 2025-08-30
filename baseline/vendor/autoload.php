<?php

// Simple autoloader for Tipsy Framework
// Compatible with PHP 5.5+

spl_autoload_register(function ($class) {
    // Handle Tipsy namespace
    if (strpos($class, 'Tipsy\\') === 0) {
        $file = __DIR__ . '/tipsy/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    // Handle App namespace
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

// Load Tipsy main class
require_once __DIR__ . '/tipsy/Tipsy.php';
