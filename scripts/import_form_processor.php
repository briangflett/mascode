<?php

/**
 * Form Processor Import Tool
 *
 * Imports Form Processor configurations from JSON files and converts them for current environment.
 * Supports importing from any environment (dev/prod) with automatic ID mapping and URL conversion.
 *
 * USAGE:
 *   cv scr scripts/import_form_processor.php --user=brian.flett@masadvise.org
 *
 * CONFIGURATION (edit the variables below):
 *
 * $FORM_TO_IMPORT:
 *   - Set to the name of a specific form to import (e.g., 'mailing_list_form')
 *   - Only used when $IMPORT_ALL is false
 *   - Form names are case-sensitive and must match the JSON filename (without .json)
 *
 * $IMPORT_ALL:
 *   - true:  Imports ALL form JSON files found in the forms directory
 *   - false: Imports only the form specified in $FORM_TO_IMPORT
 *
 * $SOURCE_ENVIRONMENT:
 *   - 'auto': Auto-detect source environment from export metadata or content
 *   - 'dev':  Treat source as dev format (will convert to current environment)
 *   - 'prod': Treat source as prod format (will convert to current environment)
 *   - 'current': Import as-is without conversion
 *
 * $LIST_ONLY:
 *   - true:  Only lists available import files and exits (no import)
 *   - false: Normal import behavior
 *
 * $UPDATE_EXISTING:
 *   - true:  Updates existing forms if they already exist
 *   - false: Skips forms that already exist (safer option)
 *
 * $DRY_RUN:
 *   - true:  Show what would be imported without making changes
 *   - false: Actually import the forms
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. List all available form JSON files for reference
 * 2. Load form data and detect source environment
 * 3. Apply environment-specific conversions for current environment
 * 4. Import forms using the FormProcessor API
 * 5. Handle existing forms based on $UPDATE_EXISTING setting
 * 6. Create import logs for tracking changes
 *
 * EXAMPLES:
 *
 * Import mailing_list_form with auto-detection:
 *   $FORM_TO_IMPORT = 'mailing_list_form';
 *   $SOURCE_ENVIRONMENT = 'auto';
 *   Result: Auto-detects source format and converts to current environment
 *
 * Import all forms from prod format:
 *   $IMPORT_ALL = true;
 *   $SOURCE_ENVIRONMENT = 'prod';
 *   Result: Imports all forms treating them as prod format
 *
 * @author MAS Team
 * @version 2.0 (Unified Import with Environment Conversion)
 * @requires CiviCRM 6.1+, Form Processor extension
 */

echo "=== Form Processor Import Tool ===\n\n";

// CONFIGURATION
$FORM_TO_IMPORT = '';                             // Change this to import different forms
$IMPORT_ALL = true;                              // Set to true to import all forms
$SOURCE_ENVIRONMENT = 'auto';                     // 'auto', 'dev', 'prod', or 'current'
$LIST_ONLY = false;                               // Set to true to just list available files
$UPDATE_EXISTING = true;                          // Set to true to update existing forms
$DRY_RUN = false;                                 // Set to true to preview import

// Validate source environment
if (!in_array($SOURCE_ENVIRONMENT, ['auto', 'dev', 'prod', 'current'])) {
    echo "Error: SOURCE_ENVIRONMENT must be 'auto', 'dev', 'prod', or 'current'\n";
    exit(1);
}

$currentEnv = detectCurrentEnvironment();
echo "Current environment: $currentEnv\n";
echo "Source environment: $SOURCE_ENVIRONMENT\n";

if ($DRY_RUN) {
    echo "*** DRY RUN MODE - No changes will be made ***\n";
}
echo "\n";

// Get import directory
$importDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/FormProcessor/forms');
if (!is_dir($importDir)) {
    echo "Error: Import directory does not exist: $importDir\n";
    echo "Run the export script first or check the directory path.\n";
    exit(1);
}

// Find available form files
$availableFiles = [];
$files = glob($importDir . '/*.json');
foreach ($files as $file) {
    // Skip log files
    if (strpos($file, '.export.log') !== false || strpos($file, '.conversion.log') !== false) {
        continue;
    }

    $formName = basename($file, '.json');
    $metadataFile = $importDir . '/' . $formName . '.export.log';

    $availableFiles[$formName] = [
        'json_file' => $file,
        'metadata_file' => file_exists($metadataFile) ? $metadataFile : null
    ];
}

if (empty($availableFiles)) {
    echo "No form JSON files found in: $importDir\n";
    echo "Expected files: [formname].json\n";
    exit(1);
}

echo "Available form files:\n";
foreach ($availableFiles as $formName => $files) {
    $status = $files['metadata_file'] ? '✓ With metadata' : '⚠ No metadata';
    echo "  - $formName ($status)\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($availableFiles) . " form files available.\n";
    echo "To import, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine forms to import
$formsToImport = [];
if ($IMPORT_ALL) {
    echo "\nImporting ALL available forms...\n";
    $formsToImport = array_keys($availableFiles);
} else {
    echo "\nImporting form: {$FORM_TO_IMPORT}\n";
    if (!isset($availableFiles[$FORM_TO_IMPORT])) {
        echo "Error: Form '{$FORM_TO_IMPORT}' JSON file not found.\n";
        echo "Available forms are listed above.\n";
        exit(1);
    }
    $formsToImport[] = $FORM_TO_IMPORT;
}

// Import each form
$importedForms = 0;
$skippedForms = 0;
$errorForms = 0;

foreach ($formsToImport as $formName) {
    echo "\n--- " . ($DRY_RUN ? 'Preview' : 'Importing') . " Form: $formName ---\n";

    try {
        $files = $availableFiles[$formName];

        // Read form data
        $formData = json_decode(file_get_contents($files['json_file']), true);
        if (!$formData) {
            echo "✗ Error: Invalid JSON in file: " . basename($files['json_file']) . "\n";
            $errorForms++;
            continue;
        }

        // Read metadata if available
        $metadata = [];
        if ($files['metadata_file']) {
            $metadata = json_decode(file_get_contents($files['metadata_file']), true);
            if (!$metadata) {
                echo "⚠ Warning: Could not read metadata from: " . basename($files['metadata_file']) . "\n";
                $metadata = [];
            }
        }

        // Determine source environment
        $sourceEnv = $SOURCE_ENVIRONMENT;
        if ($sourceEnv === 'auto') {
            $sourceEnv = detectSourceEnvironment($formData, $metadata);
            echo "Auto-detected source environment: $sourceEnv\n";
        }

        // Apply environment conversion if needed
        $convertedFormData = $formData;
        if ($sourceEnv !== 'current' && $sourceEnv !== $currentEnv) {
            echo "Converting from $sourceEnv to $currentEnv environment...\n";
            $convertedFormData = convertFormFromEnvironment($formData, $sourceEnv, $currentEnv);
        }

        // Check if form already exists
        $existingForm = null;
        try {
            $existingForms = civicrm_api3('FormProcessorInstance', 'get', [
                'name' => $formName,
                'options' => ['limit' => 1]
            ]);
            if (!empty($existingForms['values'])) {
                $existingForm = reset($existingForms['values']);
            }
        } catch (Exception $e) {
            // Form doesn't exist, which is fine
        }

        if ($existingForm && !$UPDATE_EXISTING) {
            echo "⚠ Skipping existing form: $formName\n";
            $skippedForms++;
            continue;
        }

        if ($DRY_RUN) {
            echo "✓ Form data loaded and converted\n";
            if ($existingForm) {
                echo "✓ Would update existing form: $formName\n";
            } else {
                echo "✓ Would create new form: $formName\n";
            }
        } else {
            // Actually import the form
            $result = importFormProcessor($convertedFormData, $existingForm);

            if ($result) {
                echo "✓ Form imported successfully: $formName\n";
                $importedForms++;

                // Create import log
                $logFile = $importDir . '/' . $formName . '.import.log';
                $logData = [
                    'imported_date' => date('Y-m-d H:i:s'),
                    'source_file' => $files['json_file'],
                    'source_environment' => $sourceEnv,
                    'target_environment' => $currentEnv,
                    'form_name' => $formName,
                    'was_update' => !empty($existingForm),
                    'import_version' => '2.0'
                ];
                file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo "✓ Import log: " . basename($logFile) . "\n";
            } else {
                echo "✗ Import failed for: $formName\n";
                $errorForms++;
            }
        }

    } catch (Exception $e) {
        echo "✗ Error " . ($DRY_RUN ? 'previewing' : 'importing') . " $formName: " . $e->getMessage() . "\n";
        $errorForms++;
    }
}

echo "\n=== " . ($DRY_RUN ? 'Preview' : 'Import') . " Complete ===\n";
echo "Forms imported: $importedForms\n";
echo "Forms skipped: $skippedForms\n";
echo "Forms with errors: $errorForms\n";

if ($importedForms > 0 && !$DRY_RUN) {
    echo "\n✓ Import successful! You may want to verify the imported forms in Form Processor admin.\n";
}

/**
 * Import form processor data
 */
function importFormProcessor($formData, $existingForm = null)
{
    try {
        // Create a temporary file for the FormProcessor import API
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'fp_import_');
        file_put_contents($tempFile, json_encode($formData, JSON_PRETTY_PRINT));

        // Use the FormProcessorInstance import API
        $result = civicrm_api3('FormProcessorInstance', 'import', [
            'file' => $tempFile,
            'import_locally' => 1
        ]);

        // Clean up temp file
        unlink($tempFile);

        return !empty($result) && empty($result['is_error']);

    } catch (Exception $e) {
        echo "Import error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Detect source environment from form data and metadata
 */
function detectSourceEnvironment($formData, $metadata)
{
    // Check metadata first
    if (!empty($metadata['target_environment'])) {
        return $metadata['target_environment'];
    }

    // Check URLs in form data
    if (!empty($formData['calculations_configuration_url'])) {
        $url = $formData['calculations_configuration_url'];
        if (strpos($url, 'masdemo.localhost') !== false) {
            return 'dev';
        }
        if (strpos($url, 'masadvise.org') !== false) {
            return 'prod';
        }
    }

    // Check common ID patterns (this is heuristic)
    if (!empty($formData['actions'])) {
        foreach ($formData['actions'] as $action) {
            if (!empty($action['configuration'])) {
                foreach ($action['configuration'] as $key => $value) {
                    if (preg_match('/location_type/', $key) && $value === '3') {
                        return 'prod'; // Work location type suggests prod
                    }
                    if (preg_match('/location_type/', $key) && $value === '1') {
                        return 'dev'; // Home location type suggests dev
                    }
                }
            }
        }
    }

    // Default to dev for safety
    return 'dev';
}

/**
 * Convert form data from source environment to target environment
 */
function convertFormFromEnvironment($formData, $sourceEnv, $targetEnv)
{
    $converted = $formData;

    // Get conversion mappings
    $mappings = getEnvironmentConversionMappings($sourceEnv, $targetEnv);

    // Convert URLs
    if (!empty($converted['calculations_configuration_url']) && !empty($mappings['url_replacements']['from'])) {
        $converted['calculations_configuration_url'] = str_replace(
            $mappings['url_replacements']['from'],
            $mappings['url_replacements']['to'],
            $converted['calculations_configuration_url']
        );
    }

    // Convert configuration IDs in actions
    if (!empty($converted['actions'])) {
        foreach ($converted['actions'] as &$action) {
            // Convert configuration values
            if (!empty($action['configuration'])) {
                foreach ($action['configuration'] as $key => &$value) {
                    $newValue = convertId($value, $key, $mappings);
                    if ($newValue !== $value) {
                        echo "  → Converted $key: $value → $newValue\n";
                        $value = $newValue;
                    }
                }
            }

            // Convert mapping values
            if (!empty($action['mapping'])) {
                foreach ($action['mapping'] as $key => &$value) {
                    $newValue = convertId($value, $key, $mappings);
                    if ($newValue !== $value) {
                        echo "  → Converted $key: $value → $newValue\n";
                        $value = $newValue;
                    }
                }
            }
        }
    }

    return $converted;
}

/**
 * Convert individual ID based on field name and mappings
 */
function convertId($value, $fieldName, $mappings)
{
    // Skip non-numeric values
    if (!is_numeric($value)) {
        return $value;
    }

    $numericValue = (int)$value;

    // Map location types
    if (preg_match('/location_type/', $fieldName) && isset($mappings['location_types'][$numericValue])) {
        return (string)$mappings['location_types'][$numericValue];
    }

    // Map phone types
    if (preg_match('/phone_type/', $fieldName) && isset($mappings['phone_types'][$numericValue])) {
        return (string)$mappings['phone_types'][$numericValue];
    }

    // Map contact types
    if (preg_match('/contact_type/', $fieldName) && isset($mappings['contact_types'][$numericValue])) {
        return (string)$mappings['contact_types'][$numericValue];
    }

    return $value;
}

/**
 * Get environment conversion mappings
 */
function getEnvironmentConversionMappings($sourceEnv, $targetEnv)
{
    if ($sourceEnv === 'dev' && $targetEnv === 'prod') {
        // Converting dev → prod
        return [
            'location_types' => [
                1 => 3,  // Home → Work
                2 => 2,  // Home → Home (if needed)
                3 => 3,  // Work → Work
            ],
            'phone_types' => [
                1 => 1,  // Phone → Phone
                2 => 2,  // Mobile → Mobile
                3 => 3,  // Fax → Fax
            ],
            'contact_types' => [
                1 => 1,  // Individual → Individual
                2 => 2,  // Household → Household
                3 => 3,  // Organization → Organization
            ],
            'url_replacements' => [
                'from' => 'masdemo.localhost',
                'to' => 'masadvise.org'
            ]
        ];
    } elseif ($sourceEnv === 'prod' && $targetEnv === 'dev') {
        // Converting prod → dev
        return [
            'location_types' => [
                3 => 1,  // Work → Home
                2 => 2,  // Home → Home (if needed)
                1 => 1,  // Home → Home
            ],
            'phone_types' => [
                1 => 1,  // Phone → Phone
                2 => 2,  // Mobile → Mobile
                3 => 3,  // Fax → Fax
            ],
            'contact_types' => [
                1 => 1,  // Individual → Individual
                2 => 2,  // Household → Household
                3 => 3,  // Organization → Organization
            ],
            'url_replacements' => [
                'from' => 'masadvise.org',
                'to' => 'masdemo.localhost'
            ]
        ];
    } else {
        // Same environment - no conversion needed
        return [
            'location_types' => [],
            'phone_types' => [],
            'contact_types' => [],
            'url_replacements' => [
                'from' => '',
                'to' => ''
            ]
        ];
    }
}

/**
 * Detect current environment
 */
function detectCurrentEnvironment()
{
    // Check for dev environment indicators
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
        strpos($_SERVER['HTTP_HOST'] ?? '', 'masdemo') !== false) {
        return 'dev';
    }

    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'masadvise.org') !== false) {
        return 'prod';
    }

    // Default to dev for safety
    return 'dev';
}
