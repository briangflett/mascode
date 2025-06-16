<?php

// USAGE:
//   cv scr scripts/temp.php
try {
    $result = \Civi\Mascode\Hook\PostInstallOrUpgradeHook::handle();
    // $result = \Civi\Mascode\Util\CodeGenerator::generate('service_request');
    print_r($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
