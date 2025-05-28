<?php

// file: Civi/Mascode/Patches/PatchManager.php

namespace Civi\Mascode\Patches;

/**
 * Manages application of patches to CiviCRM core and extensions
 */
class PatchManager
{
    /**
     * Apply all patches found in the patches directory
     *
     * @return array
     *   Array of patch results keyed by patch filename
     */
    public static function applyAll()
    {
        $results = [];

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
    public static function getAvailablePatches()
    {
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
    public static function getPatchDirectory()
    {
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
    public static function applyPatch($patchFile)
    {
        try {
            $civiRoot = \Civi::paths()->getPath('[civicrm.root]/');
            $patchFilename = basename($patchFile);

            // Check if patch is already applied by looking for target file changes
            if (self::isPatchAlreadyApplied($patchFile, $civiRoot)) {
                return [
                'success' => true,
                'message' => "Patch $patchFilename appears to be already applied",
                ];
            }

            // Apply the patch using patch command
            return self::applyPatchWithPatchCommand($patchFile, $civiRoot);
        } catch (\Exception $e) {
            \Civi::log()->error('Cannot apply patch ' . basename($patchFile) . ': ' . $e->getMessage());
            return [
            'success' => false,
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
    protected static function isPatchAlreadyApplied($patchFile, $targetDir)
    {
        // Extract target file paths from the patch
        $patchContent = file_get_contents($patchFile);
        if (!$patchContent) {
            return false;
        }

        // Parse the patch and find files being modified
        $modifiedFiles = [];
        if (preg_match_all('/\+\+\+ b\/(.+)\n/m', $patchContent, $matches)) {
            $modifiedFiles = $matches[1];
        }

        // For each file in the patch, check if it exists and has the changes
        foreach ($modifiedFiles as $modifiedFile) {
            $fullPath = $targetDir . '/' . $modifiedFile;

            // If the file doesn't exist, the patch hasn't been applied
            if (!file_exists($fullPath)) {
                return false;
            }

            // If the file exists, we need to check file content to make sure it has the changes
            $fileContent = file_get_contents($fullPath);
            if (preg_match_all('/^\+([^+].*)$/m', $patchContent, $addedLines)) {
                // Sample a few added lines from the patch
                $samplesToCheck = min(5, count($addedLines[1]));
                for ($i = 0; $i < $samplesToCheck; $i++) {
                    $idx = (int) (count($addedLines[1]) / $samplesToCheck * $i);
                    $line = trim($addedLines[1][$idx]);
                    if (!empty($line) && strpos($fileContent, $line) === false) {
                        return false;
                    }
                }
            }
        }

        // Modified file check - look for unique functions or variables
        if (preg_match_all(
            '/^\+\s*(private|protected|public|function|class)\s+(\w+)/m',
            $patchContent,
            $newFunctions
        )) {
            foreach ($newFunctions[2] as $i => $newFunction) {
                // Find the target file for this function
                if (empty($modifiedFiles)) {
                    continue;
                }

                // Just check the first file - this is a simplification
                $fullPath = $targetDir . '/' . $modifiedFiles[0];
                if (!file_exists($fullPath)) {
                    return false;
                }

                $fileContent = file_get_contents($fullPath);
                if (strpos($fileContent, $newFunction) === false) {
                    return false;
                }
            }
        }

        // If we've made it here, there's a good chance the patch has been applied
        return true;
    }

    /**
     * Apply a patch file using the standard UNIX patch command
     *
     * @param string $patchFile
     *   Full path to the patch file
     * @param string $targetDir
     *   Directory to apply the patch to (usually CiviCRM root)
     * @param int $stripPrefix
     *   Number of path components to strip from patch paths (default: 1)
     * @return array
     *   Result of the patch operation
     */
    protected static function applyPatchWithPatchCommand($patchFile, $targetDir, $stripPrefix = 1)
    {
        $patchFilename = basename($patchFile);

        // First, use --dry-run to check if the patch would apply cleanly
        $command = "cd " . escapeshellarg($targetDir) . " && patch -p{$stripPrefix} --dry-run < " .
        escapeshellarg($patchFile) . " 2>&1";
        $checkResult = shell_exec($command);

        // Check for failure in the dry run
        if (strpos($checkResult, 'FAILED') !== false) {
            return [
            'success' => false,
            'message' => "Patch $patchFilename check failed: " . trim($checkResult),
            ];
        }

        // Apply the patch for real
        $command = "cd " . escapeshellarg($targetDir) . " && patch -p{$stripPrefix} < " .
        escapeshellarg($patchFile) . " 2>&1";
        $applyResult = shell_exec($command);

        // Check for failure in the actual application
        $success = strpos($applyResult, 'FAILED') === false;

        return [
        'success' => $success,
        'message' => $success
            ? "Patch $patchFilename applied successfully"
            : "Patch $patchFilename application failed: " . trim($applyResult),
        ];
    }

    /**
     * Check if the patch command is available
     *
     * @return bool
     *   TRUE if patch is available
     */
    public static function isPatchAvailable()
    {
        exec('patch --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Check if git is available (for backward compatibility)
     *
     * @return bool
     *   TRUE if git is available
     */
    public static function isGitAvailable()
    {
        exec('git --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }
}
