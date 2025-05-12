<?php
// temporary script to execute a command and see the output
try {
    $result = \Civi\Mascode\Hook\PostInstallOrUpgradeHook::handle();
    print_r($result);
 } catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
 }
