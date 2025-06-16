<?php

/**
 * Afform Import Tool - Unified Version
 *
 * Imports CiviCRM Afforms from JSON files and converts them for current environment.
 * Supports importing from any environment (dev/prod) with automatic ID mapping and URL conversion.
 *
 * USAGE:
 *   cv scr scripts/import_afform.php --user=brian.flett@masadvise.org
 *
 * CONFIGURATION (edit the variables below):
 *
 * $FORM_TO_IMPORT:
 *   - Set to the name of a specific form to import (e.g., 'afformMASRCSForm')
 *   - Only used when $IMPORT_ALL is false
 *   - Form names are case-sensitive and must match the JSON filename (without .get.json)
 *
 * $IMPORT_ALL:
 *   - true:  Imports ALL form .get.json files found in the Afforms directory
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
 * 1. List all available Afform JSON files for reference
 * 2. Load form data and detect source environment
 * 3. Apply environment-specific conversions for current environment
 * 4. Import forms using the Afform API
 * 5. Handle existing forms based on $UPDATE_EXISTING setting
 * 6. Create import logs for tracking changes
 *
 * @author MAS Team
 * @version 2.0 (Unified Import with Environment Conversion)
 * @requires CiviCRM 6.1+, Afform extension
 */

echo "=== Afform Import Tool (Unified) ===\n\n";

// CONFIGURATION
$FORM_TO_IMPORT = 'afformMASRCSForm';  // Change this to import different forms
$IMPORT_ALL = false;                   // Set to true to import all forms
$SOURCE_ENVIRONMENT = 'auto';          // 'auto', 'dev', 'prod', or 'current'
$LIST_ONLY = false;                   // Set to true to just list available files
$UPDATE_EXISTING = true;              // Set to true to update existing forms
$DRY_RUN = false;                     // Set to true to preview import

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
$importDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/Afforms');
if (!is_dir($importDir)) {
    echo "Error: Import directory does not exist: $importDir\n";
    echo "Run the export script first or check the directory path.\n";
    exit(1);
}

// Find available form files
$availableFiles = [];
$files = glob($importDir . '/*.get.json');
foreach ($files as $file) {
    $formName = basename($file, '.get.json');
    $mappingsFile = $importDir . '/' . $formName . '.mappings.json';
    $metadataFile = $importDir . '/' . $formName . '.export.log';

    $availableFiles[$formName] = [
        'get_file' => $file,
        'mappings_file' => file_exists($mappingsFile) ? $mappingsFile : null,
        'metadata_file' => file_exists($metadataFile) ? $metadataFile : null
    ];
}

if (empty($availableFiles)) {
    echo "No Afform .get.json files found in: $importDir\n";
    echo "Expected files: [formname].get.json\n";
    exit(1);
}

echo "Available Afform files:\n";
foreach ($availableFiles as $formName => $files) {
    $metadataStatus = $files['metadata_file'] ? '✓ With metadata' : '⚠ No metadata';
    $mappingsStatus = $files['mappings_file'] ? '✓ With mappings' : '⚠ No mappings';
    echo "  - $formName ($metadataStatus, $mappingsStatus)\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($availableFiles) . " Afform files available.\n";
    echo "To import, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine forms to import
$formsToImport = [];
if ($IMPORT_ALL) {
    echo "\nImporting ALL available Afforms...\n";
    $formsToImport = array_keys($availableFiles);
} else {
    echo "\nImporting form: {$FORM_TO_IMPORT}\n";
    if (!isset($availableFiles[$FORM_TO_IMPORT])) {
        echo "Error: Form '{$FORM_TO_IMPORT}' .get.json file not found.\n";
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
        $formData = json_decode(file_get_contents($files['get_file']), true);
        if (!$formData) {
            echo "✗ Error: Invalid JSON in file: " . basename($files['get_file']) . "\n";
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

        // Read mappings if available
        $mappings = [];
        if ($files['mappings_file']) {
            $mappings = json_decode(file_get_contents($files['mappings_file']), true);
            if (!$mappings) {
                echo "⚠ Warning: Could not read mappings from: " . basename($files['mappings_file']) . "\n";
                $mappings = [];
            }
        }

        // Determine source environment
        $sourceEnv = $SOURCE_ENVIRONMENT;
        if ($sourceEnv === 'auto') {
            $sourceEnv = detectAfformSourceEnvironment($formData, $metadata);
            echo "Auto-detected source environment: $sourceEnv\n";
        }

        // Apply environment conversion if needed
        $convertedFormData = $formData;
        if ($sourceEnv !== 'current' && $sourceEnv !== $currentEnv) {
            echo "Converting from $sourceEnv to $currentEnv environment...\n";
            $convertedFormData = convertAfformFromEnvironment($formData, $mappings, $sourceEnv, $currentEnv);
        }

        // Check if form already exists
        $existingForm = null;
        try {
            $existingForms = \Civi\Api4\Afform::get()
                ->addWhere('name', '=', $formName)
                ->execute();
            $existingForm = $existingForms->first();
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
            $result = importAfform($convertedFormData, $existingForm);

            if ($result) {
                echo "✓ Form imported successfully: $formName\n";
                $importedForms++;

                // Create import log
                $logFile = $importDir . '/' . $formName . '.import.log';
                $logData = [
                    'imported_date' => date('Y-m-d H:i:s'),
                    'source_file' => $files['get_file'],
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
    echo "\n✓ Import successful! You may want to verify the imported forms in Afform admin.\n";
}

/**
 * Import Afform data
 */
function importAfform($formData, $existingForm = null)
{
    try {
        // Use the Afform API to save the form
        $result = \Civi\Api4\Afform::save()
            ->addRecord($formData)
            ->execute();

        return !empty($result);

    } catch (Exception $e) {
        echo "Import error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Detect source environment from form data and metadata
 */
function detectAfformSourceEnvironment($formData, $metadata)
{
    // Check metadata first
    if (!empty($metadata['target_environment'])) {
        return $metadata['target_environment'];
    }

    // Check common ID patterns in layout (this is heuristic)
    if (!empty($formData['layout'])) {
        $ids = extractLocationTypeIds($formData['layout']);
        foreach ($ids as $id) {
            if ($id === 3) {
                return 'prod'; // Work location type suggests prod
            }
            if ($id === 1) {
                return 'dev'; // Home location type suggests dev
            }
        }
    }

    // Default to dev for safety
    return 'dev';
}

/**
 * Extract location type IDs from layout for environment detection
 */
function extractLocationTypeIds($layout)
{
    $ids = [];

    if (!is_array($layout)) {
        return $ids;
    }

    foreach ($layout as $element) {
        if (is_array($element)) {
            // Check for location type in defn
            if (isset($element['defn']['afform_default']) && isset($element['name']) &&
                $element['name'] === 'location_type_id') {
                $ids[] = (int)$element['defn']['afform_default'];
            }

            // Recursively check children
            if (isset($element['#children'])) {
                $ids = array_merge($ids, extractLocationTypeIds($element['#children']));
            }
        }
    }

    return $ids;
}

/**
 * Convert Afform data from source environment to target environment
 */
function convertAfformFromEnvironment($formData, $mappings, $sourceEnv, $targetEnv)
{
    $converted = $formData;

    // Get conversion mappings
    $conversionMappings = getAfformEnvironmentConversionMappings($sourceEnv, $targetEnv);

    // Convert email confirmation template ID
    if (!empty($converted['email_confirmation_template_id'])) {
        $devId = $converted['email_confirmation_template_id'];
        if (!empty($mappings['message_templates'][$devId])) {
            $templateName = $mappings['message_templates'][$devId];
            $prodId = lookupAfformMessageTemplateId($templateName);
            if ($prodId) {
                $converted['email_confirmation_template_id'] = $prodId;
                echo "  → Mapped message template '$templateName': $devId → $prodId\n";
            }
        }
    }

    // Convert layout IDs
    if (!empty($converted['layout'])) {
        $converted['layout'] = convertAfformLayoutFromEnvironment($converted['layout'], $mappings, $conversionMappings);
    }

    return $converted;
}

/**
 * Recursively convert IDs in layout structure from source to target environment
 */
function convertAfformLayoutFromEnvironment($layout, $mappings, $conversionMappings)
{
    if (!is_array($layout)) {
        return $layout;
    }

    foreach ($layout as &$element) {
        if (is_array($element)) {
            // Convert case_type_id in data
            if (isset($element['data']['case_type_id']) && !empty($mappings['case_types'])) {
                $devId = $element['data']['case_type_id'];
                if (isset($mappings['case_types'][$devId])) {
                    $caseTypeName = $mappings['case_types'][$devId];
                    $prodId = lookupAfformCaseTypeId($caseTypeName);
                    if ($prodId) {
                        $element['data']['case_type_id'] = $prodId;
                        echo "  → Mapped case type '$caseTypeName': $devId → $prodId\n";
                    }
                }
            }

            // Convert afform_default values
            if (isset($element['defn']['afform_default'])) {
                $devValue = $element['defn']['afform_default'];
                $fieldName = $element['name'] ?? '';

                if ($fieldName === 'country_id' && !empty($mappings['countries'][$devValue])) {
                    $countryName = $mappings['countries'][$devValue];
                    $prodId = lookupAfformCountryId($countryName);
                    if ($prodId) {
                        $element['defn']['afform_default'] = (string)$prodId;
                        echo "  → Mapped country '$countryName': $devValue → $prodId\n";
                    }
                } elseif ($fieldName === 'state_province_id' && !empty($mappings['state_provinces'][$devValue])) {
                    $provinceName = $mappings['state_provinces'][$devValue];
                    $prodId = lookupAfformStateProvinceId($provinceName);
                    if ($prodId) {
                        $element['defn']['afform_default'] = $prodId;
                        echo "  → Mapped province '$provinceName': $devValue → $prodId\n";
                    }
                } elseif ($fieldName === 'location_type_id' && isset($conversionMappings['location_types'][$devValue])) {
                    $prodId = $conversionMappings['location_types'][$devValue];
                    $element['defn']['afform_default'] = (string)$prodId;
                    echo "  → Mapped location type: $devValue → $prodId\n";
                } elseif ($fieldName === 'phone_type_id' && isset($conversionMappings['phone_types'][$devValue])) {
                    $prodId = $conversionMappings['phone_types'][$devValue];
                    $element['defn']['afform_default'] = (string)$prodId;
                    echo "  → Mapped phone type: $devValue → $prodId\n";
                }
            }

            // Recursively process children
            if (isset($element['#children'])) {
                $element['#children'] = convertAfformLayoutFromEnvironment($element['#children'], $mappings, $conversionMappings);
            }
        }
    }

    return $layout;
}

/**
 * Get environment conversion mappings for Afforms
 */
function getAfformEnvironmentConversionMappings($sourceEnv, $targetEnv)
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
            ]
        ];
    } else {
        // Same environment - no conversion needed
        return [
            'location_types' => [],
            'phone_types' => []
        ];
    }
}

/**
 * Lookup functions for Afforms
 */
function lookupAfformMessageTemplateId($templateName)
{
    try {
        $result = \Civi\Api4\MessageTemplate::get()
            ->addWhere('msg_title', '=', $templateName)
            ->addSelect('id')
            ->execute()
            ->first();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        echo "Warning: Could not lookup message template '$templateName': " . $e->getMessage() . "\n";
        return null;
    }
}

function lookupAfformCaseTypeId($caseTypeName)
{
    try {
        $result = \Civi\Api4\CaseType::get()
            ->addWhere('name', '=', $caseTypeName)
            ->addSelect('id')
            ->execute()
            ->first();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        echo "Warning: Could not lookup case type '$caseTypeName': " . $e->getMessage() . "\n";
        return null;
    }
}

function lookupAfformCountryId($countryName)
{
    try {
        $result = \Civi\Api4\Country::get()
            ->addWhere('name', '=', $countryName)
            ->addSelect('id')
            ->execute()
            ->first();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        echo "Warning: Could not lookup country '$countryName': " . $e->getMessage() . "\n";
        return null;
    }
}

function lookupAfformStateProvinceId($provinceName)
{
    try {
        $result = \Civi\Api4\StateProvince::get()
            ->addWhere('name', '=', $provinceName)
            ->addSelect('id')
            ->execute()
            ->first();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        echo "Warning: Could not lookup state/province '$provinceName': " . $e->getMessage() . "\n";
        return null;
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
