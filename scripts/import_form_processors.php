<?php

/**
 * FormProcessor Import Tool
 *
 * Imports FormProcessors from exported JSON files using FormProcessor's
 * built-in import functionality. No ID mapping required as FormProcessor
 * handles cross-environment deployment natively.
 *
 * USAGE:
 *   cv scr scripts/import_form_processors.php
 *
 * CONFIGURATION (edit the variables below):
 *
 * $PROCESSOR_TO_IMPORT:
 *   - Set to the name of a specific processor to import (e.g., 'request_for_assistance_form')
 *   - Only used when $IMPORT_ALL is false
 *   - Processor names are case-sensitive and must match the JSON filename (without .json)
 *
 * $IMPORT_ALL:
 *   - true:  Imports ALL processor JSON files found in the directory
 *   - false: Imports only the processor specified in $PROCESSOR_TO_IMPORT
 *
 * $LIST_ONLY:
 *   - true:  Only lists available processor files and exits (no import)
 *   - false: Normal import behavior
 *
 * $UPDATE_EXISTING:
 *   - true:  Updates existing processors if they already exist
 *   - false: Skips processors that already exist (safer option)
 *
 * $DRY_RUN:
 *   - true:  Show what would be imported without making changes
 *   - false: Actually import the processors
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. List all available processor JSON files for reference
 * 2. Use FormProcessor's built-in import methods to import processors
 * 3. Handle conflicts with existing processors based on $UPDATE_EXISTING
 * 4. Validate imported processors
 * 5. Activate imported processors based on their original state
 *
 * INPUT FILES:
 *
 * Processors are imported from:
 *   - Civi/Mascode/FormProcessor/{processorname}.json - Complete processor definition
 *
 * EXAMPLES:
 *
 * List available processor files:
 *   $LIST_ONLY = true;
 *   Result: Shows all available processor JSON files and exits
 *
 * Import single processor:
 *   $PROCESSOR_TO_IMPORT = 'request_for_assistance_form';
 *   $IMPORT_ALL = false;
 *   Result: Imports the specified processor
 *
 * Import all processors:
 *   $IMPORT_ALL = true;
 *   Result: Imports every processor JSON file found
 *
 * NOTES:
 *
 * - Uses FormProcessor's built-in import functionality (no ID mapping needed)
 * - Processor names must be unique - duplicates will be skipped or updated
 * - FormProcessor handles cross-environment compatibility automatically
 * - Safe to run multiple times with $UPDATE_EXISTING = false
 *
 * ERROR HANDLING:
 *
 * - Missing JSON files are skipped with warning messages
 * - Invalid JSON format will halt import with error details
 * - FormProcessor import errors are caught and reported
 * - Database constraint violations are caught and reported
 *
 * @author MAS Team
 * @version 1.0
 * @requires CiviCRM 6.1+, FormProcessor extension
 */

echo "=== FormProcessor Import Tool ===\n\n";

// CONFIGURATION
$PROCESSOR_TO_IMPORT = '';                   // Change this to import specific processor
$IMPORT_ALL = true;                          // Set to true to import all available processors
$LIST_ONLY = false;                          // Set to true to just list available files
$UPDATE_EXISTING = true;                    // Set to true to update existing processors
$DRY_RUN = false;                           // Set to true to preview changes without importing

// Check if FormProcessor extension is available
if (!function_exists('civicrm_api3')) {
    echo "Error: CiviCRM API not available.\n";
    exit(1);
}

try {
    $testResult = civicrm_api3('FormProcessor', 'get', ['sequential' => 1, 'options' => ['limit' => 1]]);
} catch (Exception $e) {
    echo "Error: FormProcessor extension not found or not enabled.\n";
    echo "Please install and enable the FormProcessor extension.\n";
    echo "Error details: " . $e->getMessage() . "\n";
    exit(1);
}

// Define paths
$importDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/FormProcessor');

// Check if import directory exists
if (!is_dir($importDir)) {
    echo "Error: FormProcessor directory not found: $importDir\n";
    echo "Make sure you've exported processors first using export_form_processors.php\n";
    exit(1);
}

// Get available processor files
$processorFiles = glob($importDir . '/*.json');
if (empty($processorFiles)) {
    echo "No processor JSON files found in: $importDir\n";
    echo "Make sure you've exported processors first using export_form_processors.php\n";
    exit(1);
}

echo "Available processor files:\n";
foreach ($processorFiles as $file) {
    $processorName = basename($file, '.json');
    echo "  - $processorName\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($processorFiles) . " processor files available.\n";
    echo "To import, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine files to import
$filesToImport = [];
if ($IMPORT_ALL) {
    echo "\nImporting ALL available processors...\n";
    $filesToImport = $processorFiles;
} else {
    echo "\nImporting processor: {$PROCESSOR_TO_IMPORT}\n";
    $targetFile = $importDir . '/' . $PROCESSOR_TO_IMPORT . '.json';
    if (file_exists($targetFile)) {
        $filesToImport[] = $targetFile;
    } else {
        echo "Error: Processor file not found: $targetFile\n";
        echo "Available files are listed above.\n";
        exit(1);
    }
}

if ($DRY_RUN) {
    echo "\n*** DRY RUN MODE - No changes will be made ***\n";
}

// Import processors
echo "\n--- " . ($DRY_RUN ? 'Preview' : 'Importing') . " Processors ---\n";
$importedProcessors = 0;
$skippedProcessors = 0;
$errorProcessors = 0;

foreach ($filesToImport as $processorFile) {
    $processorName = basename($processorFile, '.json');
    echo "\n--- " . ($DRY_RUN ? 'Preview' : 'Importing') . " Processor: $processorName ---\n";

    try {
        // Load processor data
        $content = file_get_contents($processorFile);
        $processorData = json_decode($content, true);

        if (!$processorData) {
            echo "✗ Error: Invalid JSON in file: $processorFile\n";
            $errorProcessors++;
            continue;
        }

        // Check if processor already exists
        $existingProcessor = null;
        try {
            $existingProcessor = civicrm_api3('FormProcessor', 'getsingle', [
                'name' => $processorData['name'],
            ]);
        } catch (Exception $e) {
            // Processor doesn't exist, which is fine
        }

        if ($existingProcessor && !$UPDATE_EXISTING) {
            echo "⚠ Skipping existing processor: {$processorData['name']}\n";
            $skippedProcessors++;
            continue;
        }

        if ($DRY_RUN) {
            echo "✓ Processor data loaded and validated\n";
            echo "✓ Contains " . count($processorData['inputs'] ?? []) . " inputs and " . count($processorData['actions'] ?? []) . " actions\n";
            if ($existingProcessor) {
                echo "✓ Would update existing processor: {$processorData['name']}\n";
            } else {
                echo "✓ Would create new processor: {$processorData['name']}\n";
            }
        } else {
            // Actually import the processor
            $success = importFormProcessor($processorData, $existingProcessor);

            if ($success) {
                echo "✓ Processor imported successfully: {$processorData['name']}\n";
                $importedProcessors++;
            } else {
                echo "✗ Failed to import processor: {$processorData['name']}\n";
                $errorProcessors++;
            }
        }

    } catch (Exception $e) {
        echo "✗ Error " . ($DRY_RUN ? 'previewing' : 'importing') . " processor $processorName: " . $e->getMessage() . "\n";
        $errorProcessors++;
    }
}

echo "\n=== " . ($DRY_RUN ? 'Preview' : 'Import') . " Complete ===\n";
echo "Processors " . ($DRY_RUN ? 'previewed' : 'imported') . ": $importedProcessors\n";
echo "Processors skipped: $skippedProcessors\n";
echo "Processors with errors: $errorProcessors\n";

if (!$DRY_RUN && $importedProcessors > 0) {
    echo "\n✓ Import successful! You may want to verify the imported processors in FormProcessor admin.\n";
}

/**
 * Import a FormProcessor using available import methods
 */
function importFormProcessor($processorData, $existingProcessor = null)
{
    // Method 1: Try using FormProcessor's built-in import functionality
    $importUtilClasses = [
        'CRM_FormProcessor_Utils_Importer',
        'CRM_FormProcessor_BAO_FormProcessorInstance',
    ];

    foreach ($importUtilClasses as $utilClass) {
        if (class_exists($utilClass)) {
            try {
                $reflection = new ReflectionClass($utilClass);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $method) {
                    $methodName = $method->getName();
                    if (stripos($methodName, 'import') !== false && !$method->isConstructor()) {
                        echo "  Trying import method: {$utilClass}::{$methodName}\n";

                        // Try to call the method if it looks like an import method
                        if ($method->isStatic() && $method->getNumberOfRequiredParameters() <= 2) {
                            try {
                                $result = $utilClass::$methodName($processorData);
                                if ($result) {
                                    echo "  ✓ Import successful using {$utilClass}::{$methodName}\n";
                                    return true;
                                }
                            } catch (Exception $e) {
                                echo "    Method failed: " . $e->getMessage() . "\n";
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                echo "  Could not use {$utilClass}: " . $e->getMessage() . "\n";
            }
        }
    }

    // Method 2: Try API3 import if available
    foreach (['import', 'importformprocessor', 'importFormProcessor'] as $action) {
        try {
            $result = civicrm_api3('FormProcessor', $action, $processorData);
            if (!empty($result['id']) || !empty($result['values'])) {
                echo "  ✓ Import successful using API3 FormProcessor.{$action}\n";
                return true;
            }
        } catch (Exception $e) {
            echo "  API3 {$action} failed: " . $e->getMessage() . "\n";
        }
    }

    // Method 3: Manual import using FormProcessor API
    try {
        echo "  Using manual import method...\n";
        return manualImportProcessor($processorData, $existingProcessor);
    } catch (Exception $e) {
        echo "  Manual import failed: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Manual import method using FormProcessor APIs
 */
function manualImportProcessor($processorData, $existingProcessor = null)
{
    try {
        // Create or update main processor
        $processorParams = [
            'name' => $processorData['name'],
            'title' => $processorData['title'],
            'description' => $processorData['description'] ?? '',
            'is_active' => $processorData['is_active'] ?? '1',
            'output_handler' => $processorData['output_handler'] ?? 'OutputAllActionOutput',
            'output_handler_configuration' => json_encode($processorData['output_handler_configuration'] ?? []),
            'enable_default_data' => $processorData['enable_default_data'] ?? '0',
            'permission' => $processorData['permission'] ?? '',
        ];

        if ($existingProcessor) {
            echo "  Updating existing processor...\n";
            $processorParams['id'] = $existingProcessor['id'];
            $processor = civicrm_api3('FormProcessor', 'create', $processorParams);
            $processorId = $existingProcessor['id'];

            // Delete existing components
            deleteProcessorComponents($processorId);
        } else {
            echo "  Creating new processor...\n";
            $processor = civicrm_api3('FormProcessor', 'create', $processorParams);
            $processorId = $processor['id'];
        }

        // Import inputs
        if (!empty($processorData['inputs'])) {
            echo "  Importing " . count($processorData['inputs']) . " inputs...\n";
            foreach ($processorData['inputs'] as $input) {
                $input['form_processor_id'] = $processorId;
                civicrm_api3('FormProcessorInput', 'create', $input);
            }
        }

        // Import actions
        if (!empty($processorData['actions'])) {
            echo "  Importing " . count($processorData['actions']) . " actions...\n";
            foreach ($processorData['actions'] as $action) {
                $action['form_processor_id'] = $processorId;
                civicrm_api3('FormProcessorAction', 'create', $action);
            }
        }

        // Import default data inputs
        if (!empty($processorData['default_data_inputs'])) {
            echo "  Importing " . count($processorData['default_data_inputs']) . " default data inputs...\n";
            foreach ($processorData['default_data_inputs'] as $defaultDataInput) {
                $defaultDataInput['form_processor_id'] = $processorId;
                civicrm_api3('FormProcessorDefaultDataInput', 'create', $defaultDataInput);
            }
        }

        // Import default data actions
        if (!empty($processorData['default_data_actions'])) {
            echo "  Importing " . count($processorData['default_data_actions']) . " default data actions...\n";
            foreach ($processorData['default_data_actions'] as $defaultDataAction) {
                $defaultDataAction['form_processor_id'] = $processorId;
                civicrm_api3('FormProcessorDefaultDataAction', 'create', $defaultDataAction);
            }
        }

        // Import validate actions
        if (!empty($processorData['validate_actions'])) {
            echo "  Importing " . count($processorData['validate_actions']) . " validate actions...\n";
            foreach ($processorData['validate_actions'] as $validateAction) {
                $validateAction['form_processor_id'] = $processorId;
                civicrm_api3('FormProcessorValidateAction', 'create', $validateAction);
            }
        }

        // Import calculate actions
        if (!empty($processorData['calculate_actions'])) {
            echo "  Importing " . count($processorData['calculate_actions']) . " calculate actions...\n";
            foreach ($processorData['calculate_actions'] as $calculateAction) {
                $calculateAction['form_processor_id'] = $processorId;
                civicrm_api3('FormProcessorCalculateAction', 'create', $calculateAction);
            }
        }

        return true;

    } catch (Exception $e) {
        echo "  Manual import error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Delete all components of a processor before reimporting
 */
function deleteProcessorComponents($processorId)
{
    $componentTypes = [
        'FormProcessorInput',
        'FormProcessorAction',
        'FormProcessorDefaultDataInput',
        'FormProcessorDefaultDataAction',
        'FormProcessorValidateAction',
        'FormProcessorCalculateAction',
    ];

    foreach ($componentTypes as $componentType) {
        try {
            $components = civicrm_api3($componentType, 'get', [
                'form_processor_id' => $processorId,
                'options' => ['limit' => 0],
            ]);

            foreach ($components['values'] as $component) {
                civicrm_api3($componentType, 'delete', ['id' => $component['id']]);
            }
        } catch (Exception $e) {
            // Component type might not exist, which is fine
        }
    }
}
