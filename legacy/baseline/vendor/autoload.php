<?php

// Simple autoload for baseline testing
// This loads the Tipsy framework from the mounted source directory

spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = str_replace('\\', '/', $class) . '.php';
    
    // Try to load from mounted tipsy source
    $tipsy_file = '/var/www/tipsy-src/' . str_replace('Tipsy/', '', $file);
    if (file_exists($tipsy_file)) {
        require_once $tipsy_file;
        return;
    }
});

// Load the main Tipsy class
require_once '/var/www/tipsy-src/Tipsy.php';