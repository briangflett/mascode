<?php

/**
 * Bootstrap file for PHPUnit tests
 * 
 * This file sets up the testing environment for the Mascode extension
 */

// Ensure we're in test mode
if (!defined('CIVICRM_TEST')) {
    define('CIVICRM_TEST', 1);
}

// Load Composer autoloader if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Try to find and load CiviCRM's test bootstrap
$possibleBootstraps = [
    // Standard buildkit location
    __DIR__ . '/../../../../../../../../tools/extensions/civicrm/test-bootstrap.php',
    // Alternative locations
    __DIR__ . '/../../../../../../../test-bootstrap.php',
    __DIR__ . '/../../../../../../test-bootstrap.php',
];

$bootstrapFound = false;
foreach ($possibleBootstraps as $bootstrap) {
    if (file_exists($bootstrap)) {
        require_once $bootstrap;
        $bootstrapFound = true;
        break;
    }
}

if (!$bootstrapFound) {
    // Fallback: try to bootstrap CiviCRM manually
    $civicrm_paths = [
        __DIR__ . '/../../../../../../../../tools/extensions/civicrm',
        __DIR__ . '/../../../../../../../',
        '/var/www/html/sites/all/modules/civicrm',
    ];
    
    foreach ($civicrm_paths as $path) {
        $civicrm_config = $path . '/civicrm.config.php';
        if (file_exists($civicrm_config)) {
            require_once $civicrm_config;
            $bootstrapFound = true;
            break;
        }
    }
}

if (!$bootstrapFound) {
    echo "Warning: Could not find CiviCRM bootstrap. Some tests may fail.\n";
    echo "Make sure you're running tests from within a CiviCRM environment.\n";
}

// Load extension files
require_once __DIR__ . '/../mascode.civix.php';
require_once __DIR__ . '/../mascode.php';

// Set up test database connection if available
if (function_exists('civicrm_initialize')) {
    try {
        civicrm_initialize();
    } catch (Exception $e) {
        echo "Warning: Could not initialize CiviCRM: " . $e->getMessage() . "\n";
    }
}

// Load test utilities
require_once __DIR__ . '/TestCase.php';