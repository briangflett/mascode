<?php
// file: Civi/Mascode/Patches/PatchManager.php

namespace Civi\Mascode\Patches;

/**
 * Manages application of patches to CiviCRM core and extensions
 */
class PatchManager {
  
  /**
   * Apply all patches found in the patches directory
   *
   * @return array
   *   Array of patch results keyed by patch filename
   */
  public static function applyAll() {
    $results = [];
    
    // Apply the GenericHookEvent patch using the existing class
    $results['GenericHookEvent'] = GenericHookEventPatch::apply();
    
    // Apply all patches in the files directory
    $patchFiles = self::getAvailablePatches();
    foreach ($patchFiles as $patchFile) {
      $results[basename($patchFile)] = self::applyPatch($patchFile);
    }
    
    return $results;
  }
  
  /**
   * Get all available patch files from the patch directory
   *
   * @return array
   *   List of full paths to patch files
   */
  public static function getAvailablePatches() {
    $patchDir = self::getPatchDirectory();
    
    if (!is_dir($patchDir)) {
      return [];
    }
    
    $files = glob($patchDir . '/*.patch');
    return $files;
  }
  
  /**
   * Get the directory where patch files are stored
   *
   * @return string
   *   Full path to the patches directory
   */
  public static function getPatchDirectory() {
    return \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/Patches/files');
  }
  
  /**
   * Apply a specific patch file
   *
   * @param string $patchFile
   *   Full path to the patch file
   * @return array
   *   Result of the patch operation
   */
  public static function applyPatch($patchFile) {
    try {
      $civiRoot = \Civi::paths()->getPath('[civicrm.root]/');
      $patchFilename = basename($patchFile);
      
      // Check if patch is already applied by looking for target file changes
      if (self::isPatchAlreadyApplied($patchFile, $civiRoot)) {
        return [
          'success' => TRUE,
          'message' => "Patch $patchFilename appears to be already applied",
        ];
      }
      
      // Apply the patch using git apply
      return self::applyPatchWithGit($patchFile, $civiRoot);
    } 
    catch (\Exception $e) {
      \Civi::log()->error('Cannot apply patch ' . basename($patchFile) . ': ' . $e->getMessage());
      return [
        'success' => FALSE,
        'message' => $e->getMessage(),
      ];
    }
  }
  
  /**
   * Check if a patch has already been applied
   *
   * @param string $patchFile
   *   Full path to the patch file
   * @param string $targetDir
   *   Directory to check against (usually CiviCRM root)
   * @return bool
   *   TRUE if the patch appears to be already applied
   */
  protected static function isPatchAlreadyApplied($patchFile, $targetDir) {
    // Extract target file paths from the patch
    $patchContent = file_get_contents($patchFile);
    if (!$patchContent) {
      return FALSE;
    }
    
    // Parse the patch and find new files being created
    $newFiles = [];
    if (preg_match_all('/\+\+\+ b\/(.+)\n/m', $patchContent, $matches)) {
      $newFiles = $matches[1];
    }
    
    // For each new file in the patch, check if it exists
    foreach ($newFiles as $newFile) {
      $fullPath = $targetDir . '/' . $newFile;
      
      // If the file doesn't exist, the patch hasn't been applied
      if (!file_exists($fullPath)) {
        return FALSE;
      }
      
      // If the file exists, we need to check file content to make sure it has the changes
      // For simplicity, we just look for a few unique lines from the patch
      $fileContent = file_get_contents($fullPath);
      if (preg_match_all('/^\+([^+].*)$/m', $patchContent, $addedLines)) {
        // Sample a few added lines from the patch
        $samplesToCheck = min(5, count($addedLines[1]));
        for ($i = 0; $i < $samplesToCheck; $i++) {
          $idx = (int) (count($addedLines[1]) / $samplesToCheck * $i);
          $line = trim($addedLines[1][$idx]);
          if (!empty($line) && strpos($fileContent, $line) === FALSE) {
            return FALSE;
          }
        }
      }
    }
    
    // Modified file check - look for unique functions or variables
    if (preg_match_all('/^\+\s*(private|protected|public|function|class)\s+(\w+)/m', $patchContent, $newFunctions)) {
      foreach ($newFunctions[2] as $i => $newFunction) {
        // Find the target file for this function
        if (empty($newFiles)) {
          continue;
        }
        
        // Just check the first file - this is a simplification
        $fullPath = $targetDir . '/' . $newFiles[0];
        if (!file_exists($fullPath)) {
          return FALSE;
        }
        
        $fileContent = file_get_contents($fullPath);
        if (strpos($fileContent, $newFunction) === FALSE) {
          return FALSE;
        }
      }
    }
    
    // If we've made it here, there's a good chance the patch has been applied
    return TRUE;
  }
  
  /**
   * Apply a patch file using the git apply command
   *
   * @param string $patchFile
   *   Full path to the patch file
   * @param string $targetDir
   *   Directory to apply the patch to (usually CiviCRM root)
   * @return array
   *   Result of the patch operation
   */
  protected static function applyPatchWithGit($patchFile, $targetDir) {
    $patchFilename = basename($patchFile);
    
    // First check if the patch would apply cleanly
    $command = "cd " . escapeshellarg($targetDir) . " && git apply --check " . escapeshellarg($patchFile) . " 2>&1";
    $checkResult = shell_exec($command);
    
    if (!empty($checkResult)) {
      return [
        'success' => FALSE,
        'message' => "Patch $patchFilename check failed: $checkResult",
      ];
    }
    
    // If the check passed, apply the patch
    $command = "cd " . escapeshellarg($targetDir) . " && git apply " . escapeshellarg($patchFile) . " 2>&1";
    $applyResult = shell_exec($command);
    
    return [
      'success' => empty($applyResult),
      'message' => empty($applyResult) 
        ? "Patch $patchFilename applied successfully" 
        : "Patch $patchFilename application failed: $applyResult",
    ];
  }
  
  /**
   * Check if git is available
   *
   * @return bool
   *   TRUE if git is available
   */
  public static function isGitAvailable() {
    exec('git --version 2>&1', $output, $returnCode);
    return $returnCode === 0;
  }
}
