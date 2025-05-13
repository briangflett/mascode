<?php
// apply patches after every upgrade to CiviCRM
try {
    // Apply patches
    $result = \Civi\Mascode\Patches\GenericHookEventPatch::apply();
    print_r($result);
 } catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
 }
