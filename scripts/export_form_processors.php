<?php

/**
 * FormProcessor Export Tool (Corrected Version)
 *
 * Uses the actual FormProcessor API and BAO classes to export processors
 * in a format compatible with FormProcessor's import functionality.
 *
 * USAGE:
 *   cv scr scripts/export_formprocessor.php
 *
 * CONFIGURATION (edit the variables below):
 *
 * $PROCESSOR_TO_EXPORT:
 *   - Set to the name of a specific processor to export (e.g., 'request_for_assistance_form')
 *   - Only used when $EXPORT_ALL is false
 *   - Processor names are case-sensitive and must match exactly
 *
 * $EXPORT_ALL:
 *   - true:  Exports ALL FormProcessors (or those matching $FILTER_PREFIX)
 *   - false: Exports only the processor specified in $PROCESSOR_TO_EXPORT
 *
 * $FILTER_PREFIX:
 *   - Used when $EXPORT_ALL is true to filter processors by name prefix
 *   - Set to 'mas_' to export only MAS-specific processors
 *   - Set to '' (empty) to export all processors
 *
 * $LIST_ONLY:
 *   - true:  Only lists available processors and exits (no export)
 *   - false: Normal export behavior
 *
 * @author MAS Team
 * @version 2.1
 * @requires CiviCRM 6.1+, FormProcessor extension
 */

// scripts/export_formprocessor.php
// Uses actual FormProcessor classes and API methods

echo "=== FormProcessor Export Tool (Corrected) ===\n\n";

// CONFIGURATION
$PROCESSOR_TO_EXPORT = '';  // Change this to export different processors
$EXPORT_ALL = false;                                   // Set to true to export all (or filtered) processors
$FILTER_PREFIX = '';                                   // Filter prefix when $EXPORT_ALL is true (empty = all)
$LIST_ONLY = true;                                    // Set to true to just list available processors

// Check if FormProcessor extension is available
if (!function_exists('civicrm_api3')) {
    echo "Error: CiviCRM API not available.\n";
    exit(1);
}

// Test if FormProcessor extension is enabled by checking if we can call its API
try {
    $testResult = civicrm_api3('FormProcessor', 'get', ['sequential' => 1, 'options' => ['limit' => 1]]);
} catch (Exception $e) {
    echo "Error: FormProcessor extension not found or not enabled.\n";
    echo "Please install and enable the FormProcessor extension.\n";
    echo "Error details: " . $e->getMessage() . "\n";
    exit(1);
}

// Check available FormProcessor classes
$availableClasses = [];
$formProcessorClasses = [
    'CRM_FormProcessor_BAO_FormProcessorInstance',
    'CRM_FormProcessor_BAO_FormProcessorAction',
    'CRM_FormProcessor_BAO_FormProcessorInput',
    'CRM_FormProcessor_BAO_FormProcessorOutput',
    'CRM_FormProcessor_Utils_Importer', // This one might exist
];

foreach ($formProcessorClasses as $className) {
    if (class_exists($className)) {
        $availableClasses[] = $className;
    }
}

echo "Available FormProcessor classes:\n";
foreach ($availableClasses as $class) {
    echo "  - {$class}\n";
}
echo "\n";

// Get available FormProcessors using API3
try {
    $result = civicrm_api3('FormProcessor', 'get', [
        'sequential' => 1,
        'return' => ['id', 'name', 'title', 'description', 'is_active'],
        'options' => ['limit' => 0], // Get all
    ]);

    $processors = $result['values'];

    // Apply filter if needed
    if ($EXPORT_ALL && !empty($FILTER_PREFIX)) {
        $processors = array_filter($processors, function ($processor) use ($FILTER_PREFIX) {
            return strpos($processor['name'], $FILTER_PREFIX) === 0;
        });
    }

} catch (Exception $e) {
    echo "Error fetching FormProcessors: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($processors)) {
    $filterMsg = (!empty($FILTER_PREFIX)) ? " matching prefix '{$FILTER_PREFIX}'" : "";
    echo "No FormProcessors found{$filterMsg}!\n";
    exit(1);
}

echo "Available FormProcessors:\n";
foreach ($processors as $processor) {
    $status = $processor['is_active'] ? 'Active' : 'Inactive';
    echo "  - {$processor['name']} ({$processor['title']}) [$status]\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($processors) . " FormProcessors available.\n";
    echo "To export, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine processors to export
$processorsToExport = [];
if ($EXPORT_ALL) {
    $filterMsg = (!empty($FILTER_PREFIX)) ? " (filtered by '{$FILTER_PREFIX}')" : "";
    echo "\nExporting ALL FormProcessors{$filterMsg}...\n";
    foreach ($processors as $processor) {
        $processorsToExport[] = $processor;
    }
} else {
    echo "\nExporting processor: {$PROCESSOR_TO_EXPORT}\n";
    $processorFound = false;
    foreach ($processors as $processor) {
        if ($processor['name'] === $PROCESSOR_TO_EXPORT) {
            $processorsToExport[] = $processor;
            $processorFound = true;
            break;
        }
    }

    if (!$processorFound) {
        echo "Error: FormProcessor '{$PROCESSOR_TO_EXPORT}' not found.\n";
        echo "Available processors are listed above.\n";
        exit(1);
    }
}

// Create export directory
$exportDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/FormProcessor');

if (!is_dir($exportDir)) {
    if (!mkdir($exportDir, 0755, true)) {
        echo "Error: Could not create directory: $exportDir\n";
        exit(1);
    }
    echo "Created directory: $exportDir\n";
}

// Export each processor
foreach ($processorsToExport as $processor) {
    echo "\n--- Exporting FormProcessor: {$processor['name']} ---\n";

    try {
        $exportData = null;
        $exportMethod = 'Unknown';

        // Method 1: Try using FormProcessor's built-in export functionality
        // Check if any actual export utilities exist
        $exportUtilClasses = [
            'CRM_FormProcessor_Utils_Importer', // Sometimes has export methods too
            'CRM_FormProcessor_Page_Export',    // UI page might have export logic
            'CRM_FormProcessor_Form_Export',    // Form might have export logic
        ];

        foreach ($exportUtilClasses as $utilClass) {
            if (!$exportData && class_exists($utilClass)) {
                try {
                    $reflection = new ReflectionClass($utilClass);
                    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

                    foreach ($methods as $method) {
                        $methodName = $method->getName();
                        if (stripos($methodName, 'export') !== false && !$method->isConstructor()) {
                            echo "  Found potential export method: {$utilClass}::{$methodName}\n";

                            // Try to call the method if it looks like an export method
                            if ($method->isStatic() && $method->getNumberOfRequiredParameters() <= 1) {
                                try {
                                    $exportData = $utilClass::$methodName($processor['id']);
                                    $exportMethod = "{$utilClass}::{$methodName}";
                                    break 2; // Break out of both loops
                                } catch (Exception $e) {
                                    echo "    Method failed: " . $e->getMessage() . "\n";
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo "  Could not inspect {$utilClass}: " . $e->getMessage() . "\n";
                }
            }
        }

        // Method 2: Try API3 export if available
        if (!$exportData) {
            foreach (['export', 'exportformprocessor', 'exportFormProcessor'] as $action) {
                try {
                    $result = civicrm_api3('FormProcessor', $action, [
                        'id' => $processor['id'],
                    ]);
                    if (!empty($result['values'])) {
                        $exportData = $result['values'];
                        $exportMethod = "API3 FormProcessor.{$action}";
                        break;
                    }
                } catch (Exception $e) {
                    // This method not available, try next
                    echo "  API3 {$action} failed: " . $e->getMessage() . "\n";
                }
            }
        }

        // Method 3: Manual export using available BAO classes
        if (!$exportData) {
            echo "  Using manual export method...\n";
            $exportData = manualExportProcessor($processor['id']);
            $exportMethod = 'Manual Export';
        }

        if (!$exportData) {
            echo "✗ Could not export processor using any available method.\n";
            continue;
        }

        echo "  Export method: {$exportMethod}\n";

        // Save export data to file
        $exportFile = $exportDir . '/' . $processor['name'] . '.json';

        // Ensure proper JSON formatting
        $jsonData = is_string($exportData) ? $exportData : json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($exportFile, $jsonData) === false) {
            echo "✗ Failed to write export file: $exportFile\n";
            continue;
        }

        echo "✓ Exported to: " . basename($exportFile) . "\n";

        // Validate the exported file
        $testData = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "  Warning: Exported JSON may be invalid: " . json_last_error_msg() . "\n";
        } else {
            echo "  ✓ Export file validated successfully\n";
            echo "  ✓ Contains " . count($testData['inputs'] ?? []) . " inputs and " . count($testData['actions'] ?? []) . " actions\n";
        }

    } catch (Exception $e) {
        echo "✗ Error exporting processor {$processor['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Export Complete ===\n";
echo "Files saved to: $exportDir\n";
echo "\nTo import in target environment:\n";
echo "1. Use FormProcessor UI: Admin > Automation > Form Processor > Import\n";
echo "2. Upload the exported JSON files\n";
echo "3. Or use API if import API is available\n";

/**
 * Manual export method using actual FormProcessor API and BAO classes
 */
function manualExportProcessor($processorId)
{
    try {
        // Get main processor data
        $processorResult = civicrm_api3('FormProcessor', 'getsingle', [
            'id' => $processorId,
        ]);

        if (empty($processorResult)) {
            return null;
        }

        // Get inputs
        $inputs = [];
        try {
            $inputsResult = civicrm_api3('FormProcessorInput', 'get', [
                'sequential' => 1,
                'form_processor_id' => $processorId,
                'options' => ['limit' => 0, 'sort' => 'weight ASC'],
            ]);
            $inputs = $inputsResult['values'] ?? [];
        } catch (Exception $e) {
            echo "    Warning: Could not fetch inputs: " . $e->getMessage() . "\n";
        }

        // Get outputs
        $outputs = [];
        try {
            $outputsResult = civicrm_api3('FormProcessorOutput', 'get', [
                'sequential' => 1,
                'form_processor_id' => $processorId,
                'options' => ['limit' => 0, 'sort' => 'weight ASC'],
            ]);
            $outputs = $outputsResult['values'] ?? [];
        } catch (Exception $e) {
            echo "    Warning: Could not fetch outputs: " . $e->getMessage() . "\n";
        }

        // Get actions
        $actions = [];
        try {
            $actionsResult = civicrm_api3('FormProcessorAction', 'get', [
                'sequential' => 1,
                'form_processor_id' => $processorId,
                'options' => ['limit' => 0, 'sort' => 'weight ASC'],
            ]);
            $actions = $actionsResult['values'] ?? [];
        } catch (Exception $e) {
            echo "    Warning: Could not fetch actions: " . $e->getMessage() . "\n";
        }

        // Get default data inputs
        $defaultDataInputs = [];
        try {
            $defaultDataInputsResult = civicrm_api3('FormProcessorDefaultDataInput', 'get', [
                'sequential' => 1,
                'form_processor_id' => $processorId,
                'options' => ['limit' => 0],
            ]);
            $defaultDataInputs = $defaultDataInputsResult['values'] ?? [];
        } catch (Exception $e) {
            // Default data inputs might not exist
        }

        // Get default data actions
        $defaultDataActions = [];
        try {
            $defaultDataActionsResult = civicrm_api3('FormProcessorDefaultDataAction', 'get', [
                'sequential' => 1,
                'form_processor_id' => $processorId,
                'options' => ['limit' => 0],
            ]);
            $defaultDataActions = $defaultDataActionsResult['values'] ?? [];
        } catch (Exception $e) {
            // Default data actions might not exist
        }

        // Get validate actions
        $validateActions = [];
        try {
            $validateActionsResult = civicrm_api3('FormProcessorValidateAction', 'get', [
                'sequential' => 1,
                'form_processor_id' => $processorId,
                'options' => ['limit' => 0],
            ]);
            $validateActions = $validateActionsResult['values'] ?? [];
        } catch (Exception $e) {
            // Validate actions might not exist
        }

        // Get calculate actions
        $calculateActions = [];
        try {
            $calculateActionsResult = civicrm_api3('FormProcessorCalculateAction', 'get', [
                'sequential' => 1,
                'form_processor_id' => $processorId,
                'options' => ['limit' => 0],
            ]);
            $calculateActions = $calculateActionsResult['values'] ?? [];
        } catch (Exception $e) {
            // Calculate actions might not exist
        }

        // Build export structure
        $exportData = [
            'title' => $processorResult['title'] ?? '',
            'name' => $processorResult['name'] ?? '',
            'is_active' => $processorResult['is_active'] ?? '1',
            'description' => $processorResult['description'] ?? '',
            'output_handler' => $processorResult['output_handler'] ?? 'OutputAllActionOutput',
            'output_handler_configuration' => json_decode($processorResult['output_handler_configuration'] ?? '[]', true),
            'enable_default_data' => $processorResult['enable_default_data'] ?? '0',
            'permission' => $processorResult['permission'] ?? '',
            'inputs' => cleanInputsOutputsForExport($inputs),
            'actions' => cleanActionsForExport($actions),
            'default_data_inputs' => cleanForExport($defaultDataInputs),
            'default_data_actions' => cleanForExport($defaultDataActions),
            'validate_actions' => cleanForExport($validateActions),
            'validate_validators' => [], // Usually empty
            'calculate_actions' => cleanForExport($calculateActions),
            'default_data_output_configuration' => [],
            'calculation_output_configuration' => [],
        ];

        // Add URL if it exists (for some FormProcessor versions)
        if (!empty($processorResult['configuration_url'])) {
            $exportData['calculations_configuration_url'] = $processorResult['configuration_url'];
        }

        return $exportData;

    } catch (Exception $e) {
        echo "  Error in manual export: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Clean export data by removing environment-specific IDs and converting JSON strings
 */
function cleanForExport($data)
{
    if (!is_array($data)) {
        return $data;
    }

    $cleaned = [];
    foreach ($data as $item) {
        if (is_array($item)) {
            $cleanedItem = [];
            foreach ($item as $key => $value) {
                // Skip environment-specific fields
                if (in_array($key, ['id', 'form_processor_id', 'created_date', 'modified_date'])) {
                    continue;
                }

                // Decode JSON strings
                if (is_string($value) && (strpos($value, '{') === 0 || strpos($value, '[') === 0)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }

                $cleanedItem[$key] = $value;
            }
            $cleaned[] = $cleanedItem;
        }
    }
    return $cleaned;
}

/**
 * Special cleaning for inputs and outputs which may have additional structure
 */
function cleanInputsOutputsForExport($data)
{
    $cleaned = cleanForExport($data);

    // Ensure proper structure for inputs/outputs
    foreach ($cleaned as &$item) {
        // Ensure arrays exist
        if (!isset($item['configuration'])) {
            $item['configuration'] = [];
        }
        if (!isset($item['parameter_mapping'])) {
            $item['parameter_mapping'] = [];
        }
        if (!isset($item['default_data_parameter_mapping'])) {
            $item['default_data_parameter_mapping'] = [];
        }
        if (!isset($item['validators'])) {
            $item['validators'] = [];
        }

        // Convert weight to integer if it exists
        if (isset($item['weight'])) {
            $item['weight'] = (string)$item['weight'];
        }
    }

    return $cleaned;
}

/**
 * Special cleaning for actions which have mapping and configuration
 */
function cleanActionsForExport($data)
{
    $cleaned = cleanForExport($data);

    foreach ($cleaned as &$item) {
        // Ensure proper structure
        if (!isset($item['configuration'])) {
            $item['configuration'] = [];
        }
        if (!isset($item['mapping'])) {
            $item['mapping'] = [];
        }

        // Handle condition and delay configurations
        if (isset($item['condition_configuration']) && is_string($item['condition_configuration'])) {
            $decoded = json_decode($item['condition_configuration'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $item['condition_configuration'] = $decoded;
            } else {
                $item['condition_configuration'] = '';
            }
        }

        if (isset($item['delay_configuration']) && is_string($item['delay_configuration'])) {
            $decoded = json_decode($item['delay_configuration'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $item['delay_configuration'] = $decoded;
            } else {
                $item['delay_configuration'] = '';
            }
        }
    }

    return $cleaned;
}
