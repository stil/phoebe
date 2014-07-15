<?php
error_reporting(E_ALL);

// Ensure that composer has installed all dependencies
if (!file_exists(__DIR__ . '/../composer.lock')) {
    die("Dependencies must be installed using composer:\n\nphp composer.phar install --dev\n\n"
        . "See http://getcomposer.org for help with installing composer\n");
}

// Include the composer autoloader
require __DIR__ . '/../vendor/autoload.php';
