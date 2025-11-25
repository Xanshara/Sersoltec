<?php
/**
 * SERSOLTEC - Autoloader
 * PSR-4 Autoloader for Sersoltec\Lib namespace
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

spl_autoload_register(function ($class) {
    // Base directory for the namespace prefix
    $prefix = 'Sersoltec\\Lib\\';
    $base_dir = __DIR__ . '/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    // and append with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
