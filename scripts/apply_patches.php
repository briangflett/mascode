<?php
// scripts/apply_patches.php

// Apply all patches to CiviCRM
try {
    echo "Checking if git is available...\n";
    if (!\Civi\Mascode\Patches\PatchManager::isGitAvailable()) {
        echo "ERROR: git is not available in your environment. Cannot apply patches.\n";
        exit(1);
    }
    
    // Get list of available patches
    $patchFiles = \Civi\Mascode\Patches\PatchManager::getAvailablePatches();
    echo "Found " . count($patchFiles) . " patch files:\n";
    foreach ($patchFiles as $patchFile) {
        echo "  - " . basename($patchFile) . "\n";
    }
    echo "\n";
    
    // Apply patches
    $results = \Civi\Mascode\Patches\PatchManager::applyAll();
    
    echo "Patch application results:\n";
    $success = 0;
    $failed = 0;
    
    foreach ($results as $patchName => $result) {
        $status = $result['success'] ? "SUCCESS" : "FAILED";
        $message = $result['message'] ?? '';
        echo "  - $patchName: $status - $message\n";
        
        if ($result['success']) {
            $success++;
        } else {
            $failed++;
        }
    }
    
    echo "\nSummary: $success patches succeeded, $failed patches failed.\n";
    
    if ($failed > 0) {
        exit(1);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
