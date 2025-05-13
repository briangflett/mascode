<?php
// scripts/list_mascode_classes.php

// Determine the extension root directory (one level up from scripts)
$extensionDir = dirname(__DIR__);
echo "Extension directory: $extensionDir\n";

echo "Searching for Civi\\Mascode classes...\n";

// Method 1: Check if CiviCRM is bootstrapped
echo "\nMethod 1: Check CiviCRM bootstrap status\n";
echo "====================================\n";

if (!defined('CIVICRM_SETTINGS_PATH')) {
    echo "CiviCRM is not bootstrapped. This script should be run with 'cv scr'.\n";
} else {
    echo "CiviCRM is bootstrapped. Settings path: " . CIVICRM_SETTINGS_PATH . "\n";
}

// Method 2: Scan the filesystem for PHP files in the Mascode namespace
echo "\nMethod 2: Scan filesystem for PHP files\n";
echo "====================================\n";

$searchDir = "$extensionDir/Civi/Mascode";

if (!is_dir($searchDir)) {
    echo "Warning: Directory $searchDir does not exist!\n";
} else {
    echo "Scanning directory: $searchDir\n";
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($searchDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $files = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace($extensionDir . '/', '', $file->getPathname());
            $files[] = $relativePath;
            
            // Try to extract the class name from the file
            $content = file_get_contents($file->getPathname());
            if (preg_match('/namespace\s+([^;]+)/m', $content, $nsMatches) &&
                preg_match('/class\s+(\w+)/m', $content, $classMatches)) {
                $namespace = trim($nsMatches[1]);
                $className = trim($classMatches[1]);
                $fullClass = "$namespace\\$className";
                
                echo "$fullClass => " . $file->getPathname() . "\n";
            } else {
                echo "[Couldn't determine class] => " . $file->getPathname() . "\n";
            }
        }
    }
    
    echo "\nFound " . count($files) . " PHP files.\n";
}

// Method 3: Check CiviCRM extension registry
echo "\nMethod 3: Check CiviCRM extension registry\n";
echo "=====================================\n";

if (function_exists('civicrm_api3')) {
    try {
        $ext = civicrm_api3('Extension', 'get', ['key' => 'mascode']);
        if (!empty($ext['values'])) {
            $extInfo = reset($ext['values']);
            echo "Extension found: " . $extInfo['name'] . "\n";
            echo "Path: " . $extInfo['path'] . "\n";
            echo "Status: " . $extInfo['status'] . "\n";
        } else {
            echo "Extension 'mascode' is not registered with CiviCRM.\n";
        }
    } catch (Exception $e) {
        echo "Error checking extension: " . $e->getMessage() . "\n";
    }
}

// Method 4: Check class autoloader paths
echo "\nMethod 4: Check class autoloader paths\n";
echo "==================================\n";

if (class_exists('Civi')) {
    echo "Checking CiviCRM autoloader information:\n";
    
    try {
        // Use reflection to find information about the Civi class
        $reflection = new ReflectionClass('Civi');
        echo "  Civi class is defined in: " . $reflection->getFileName() . "\n";
        
        // Get autoloader information from Composer's registered autoloaders
        $autoloaders = spl_autoload_functions();
        echo "  Found " . count($autoloaders) . " registered autoloaders\n";
        
        $composerLoaders = [];
        foreach ($autoloaders as $idx => $autoloader) {
            if (is_array($autoloader) && 
                isset($autoloader[0]) && 
                is_object($autoloader[0]) && 
                $autoloader[0] instanceof \Composer\Autoload\ClassLoader) {
                
                $composerLoaders[] = $autoloader[0];
                echo "  Found Composer ClassLoader #" . count($composerLoaders) . "\n";
                
                // Check if this loader has Civi namespaces
                $prefixes = $autoloader[0]->getPrefixesPsr4();
                $civiPrefixes = [];
                foreach ($prefixes as $prefix => $paths) {
                    if (strpos($prefix, 'Civi\\') === 0) {
                        $civiPrefixes[$prefix] = $paths;
                    }
                }
                
                if (!empty($civiPrefixes)) {
                    echo "  ClassLoader #" . count($composerLoaders) . " contains Civi namespaces:\n";
                    foreach ($civiPrefixes as $prefix => $paths) {
                        echo "    $prefix => " . implode(', ', $paths) . "\n";
                    }
                }
            }
        }
        
        if (empty($composerLoaders)) {
            echo "  No Composer ClassLoader instances found in autoloaders\n";
        }
        
        // Try alternate method - check if Container can provide loader info
        if (method_exists('Civi', 'container') && \Civi::container()->has('civi_container_factory')) {
            echo "  Checking Container for class loading information...\n";
            $containerFactory = \Civi::container()->get('civi_container_factory');
            if (method_exists($containerFactory, 'getClassLoader')) {
                echo "  Container factory has getClassLoader method\n";
            }
        }
        
        // Get extension directories as another reference point
        $extensionSystem = \CRM_Extension_System::singleton();
        $extensionMapper = $extensionSystem->getMapper();
        $extensionDirs = $extensionMapper->getActiveModuleFiles();
        
        echo "  Active extension directories:\n";
        foreach ($extensionDirs as $key => $path) {
            if (strpos($key, 'mascode') !== false) {
                echo "    $key => $path\n";
            }
        }
        
    } catch (Throwable $e) {
        echo "  Error inspecting autoloader: " . $e->getMessage() . "\n";
    }
} else {
    echo "Civi class not available.\n";
}

// Method 5: Test if specific classes exist
echo "\nMethod 5: Test for specific classes\n";
echo "================================\n";

$testClasses = [
    'Civi\\Mascode\\Hook\\PostInstallOrUpgradeHook',
    'Civi\\Mascode\\Hook\\InstallHook',
    'Civi\\Mascode\\Hook\\CaseSummaryHook',
    'Civi\\Mascode\\CiviRules\\Action\\GenerateMasCode',
    // Add other classes you expect to be available
];

foreach ($testClasses as $class) {
    echo "$class: " . (class_exists($class) ? "EXISTS" : "NOT FOUND") . "\n";
    
    // If class exists, show the file location
    if (class_exists($class)) {
        $reflection = new ReflectionClass($class);
        echo "  File: " . $reflection->getFileName() . "\n";
    } else {
        // Try to explicitly include the file to see if that helps
        $classPath = str_replace('\\', '/', $class) . '.php';
        $fullPath = "$extensionDir/" . $classPath;
        if (file_exists($fullPath)) {
            echo "  File exists at: $fullPath\n";
            echo "  Trying to include it manually... ";
            try {
                require_once $fullPath;
                echo "included.\n";
                echo "  Class now exists: " . (class_exists($class) ? "YES" : "NO") . "\n";
            } catch (Throwable $e) {
                echo "ERROR: " . $e->getMessage() . "\n";
            }
        } else {
            echo "  File doesn't exist at expected path: $fullPath\n";
        }
    }
}

// Method 6: Test manually including the file
echo "\nMethod 6: Manually include PostInstallOrUpgradeHook\n";
echo "==============================================\n";

$hookFile = "$extensionDir/Civi/Mascode/Hook/PostInstallOrUpgradeHook.php";
echo "Checking if file exists: $hookFile\n";

if (file_exists($hookFile)) {
    echo "File exists! Trying to include it...\n";
    
    try {
        require_once $hookFile;
        echo "File included successfully.\n";
        
        $class = 'Civi\\Mascode\\Hook\\PostInstallOrUpgradeHook';
        echo "Checking if class $class exists: " . (class_exists($class) ? "YES" : "NO") . "\n";
        
        if (class_exists($class) && method_exists($class, 'handle')) {
            echo "handle() method exists. Attempting to call it...\n";
            try {
                call_user_func([$class, 'handle']);
                echo "handle() method executed successfully.\n";
            } catch (Throwable $e) {
                echo "Error calling handle(): " . $e->getMessage() . "\n";
                echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            }
        } else {
            echo "handle() method not found.\n";
        }
    } catch (Throwable $e) {
        echo "Error including file: " . $e->getMessage() . "\n";
    }
} else {
    echo "File does not exist at the expected location!\n";
}

echo "\nDone checking classes.\n";