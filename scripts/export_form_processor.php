<?php

/**
 * Form Processor Export Tool
 *
 * Exports Form Processor configurations from CiviCRM and converts them for target environment.
 * Supports exporting to any environment (dev/prod) with automatic ID mapping and URL conversion.
 *
 * USAGE:
 *   cv scr scripts/export_form_processor.php --user=brian.flett@masadvise.org
 *
 * CONFIGURATION (edit the variables below):
 *
 * $FORM_TO_EXPORT:
 *   - Set to the name of a specific form to export (e.g., 'mailing_list_form')
 *   - Only used when $EXPORT_ALL is false
 *   - Form names are case-sensitive and must match exactly
 *
 * $EXPORT_ALL:
 *   - true:  Exports ALL custom form processors (excludes core CiviCRM forms)
 *   - false: Exports only the form specified in $FORM_TO_EXPORT
 *
 * $TARGET_ENVIRONMENT:
 *   - 'dev':  Export with dev-appropriate IDs and URLs
 *   - 'prod': Export with prod-appropriate IDs and URLs
 *   - 'current': Export with current environment IDs (no conversion)
 *
 * $LIST_ONLY:
 *   - true:  Only lists available forms and exits (no export)
 *   - false: Normal export behavior
 *
 * $DRY_RUN:
 *   - true:  Show what would be exported without creating files
 *   - false: Actually export the forms
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. List all available custom form processors for reference
 * 2. Export the specified form(s) from the CiviCRM database
 * 3. Apply environment-specific conversions (IDs, URLs)
 * 4. Save to the forms directory with conversion metadata
 * 5. Create export logs for tracking changes
 *
 * EXAMPLES:
 *
 * Export mailing_list_form for prod deployment:
 *   $FORM_TO_EXPORT = 'mailing_list_form';
 *   $TARGET_ENVIRONMENT = 'prod';
 *   Result: Exports with prod IDs ready for production import
 *
 * Export all forms for dev:
 *   $EXPORT_ALL = true;
 *   $TARGET_ENVIRONMENT = 'dev';
 *   Result: Exports all forms with dev IDs
 *
 * @author MAS Team
 * @version 2.0 (Unified Export with Environment Conversion)
 * @requires CiviCRM 6.1+, Form Processor extension
 */

echo "=== Form Processor Export Tool ===\n\n";

// CONFIGURATION
$FORM_TO_EXPORT = '';                             // Change this to export different forms
$EXPORT_ALL = true;                               // Set to true to export all forms
$TARGET_ENVIRONMENT = 'current';                      // 'dev', 'prod', or 'current'
$LIST_ONLY = false;                               // Set to true to just list available forms
$DRY_RUN = false;                                 // Set to true to preview export

// Validate target environment
if (!in_array($TARGET_ENVIRONMENT, ['dev', 'prod', 'current'])) {
    echo "Error: TARGET_ENVIRONMENT must be 'dev', 'prod', or 'current'\n";
    exit(1);
}

echo "Target environment: $TARGET_ENVIRONMENT\n";
if ($DRY_RUN) {
    echo "*** DRY RUN MODE - No files will be created ***\n";
}
echo "\n";

try {
    // Get available form processors using API3
    $forms = civicrm_api3('FormProcessorInstance', 'get', [
        'options' => ['limit' => 0],
        'return' => ['id', 'name', 'title', 'is_active']
    ]);
    $forms = $forms['values'];
} catch (Exception $e) {
    echo "Error fetching form processors: " . $e->getMessage() . "\n";
    echo "Make sure the Form Processor extension is installed and enabled.\n";
    exit(1);
}

if (empty($forms)) {
    echo "No form processors found!\n";
    exit(1);
}

echo "Available form processors:\n";
foreach ($forms as $form) {
    $status = $form['is_active'] ? 'Active' : 'Inactive';
    echo "  - {$form['name']} ({$form['title']}) [$status]\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($forms) . " form processors available.\n";
    echo "To export, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine forms to export
$formsToExport = [];
if ($EXPORT_ALL) {
    echo "\nExporting ALL form processors...\n";
    foreach ($forms as $form) {
        $formsToExport[] = $form;
    }
} else {
    echo "\nExporting form: {$FORM_TO_EXPORT}\n";
    $formFound = false;
    foreach ($forms as $form) {
        if ($form['name'] === $FORM_TO_EXPORT) {
            $formsToExport[] = $form;
            $formFound = true;
            break;
        }
    }

    if (!$formFound) {
        echo "Error: Form '{$FORM_TO_EXPORT}' not found.\n";
        echo "Available forms are listed above.\n";
        exit(1);
    }
}

// Create export directory
$exportDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/FormProcessor/forms');
if (!$DRY_RUN && !is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
    echo "Created directory: $exportDir\n";
}

// Export each form
foreach ($formsToExport as $formInfo) {
    echo "\n--- " . ($DRY_RUN ? 'Preview' : 'Exporting') . " Form: {$formInfo['name']} ---\n";

    try {
        // Get complete form processor configuration
        $formData = exportFormProcessorData($formInfo['id']);

        if (!$formData) {
            echo "✗ Error: Could not export form data for {$formInfo['name']}\n";
            continue;
        }

        // Apply environment conversion
        if ($TARGET_ENVIRONMENT !== 'current') {
            echo "Converting for $TARGET_ENVIRONMENT environment...\n";
            $formData = convertFormForEnvironment($formData, $TARGET_ENVIRONMENT);
        }

        if ($DRY_RUN) {
            echo "✓ Form data loaded and converted\n";
            echo "✓ Would save to: {$exportDir}/{$formInfo['name']}.json\n";
        } else {
            // Save exported form
            $exportFile = $exportDir . '/' . $formInfo['name'] . '.json';
            file_put_contents($exportFile, json_encode($formData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Form exported: " . basename($exportFile) . "\n";

            // Create export metadata
            $metadataFile = $exportDir . '/' . $formInfo['name'] . '.export.log';
            $metadata = [
                'exported_date' => date('Y-m-d H:i:s'),
                'source_environment' => detectCurrentEnvironment(),
                'target_environment' => $TARGET_ENVIRONMENT,
                'form_id' => $formInfo['id'],
                'form_name' => $formInfo['name'],
                'form_title' => $formInfo['title'],
                'export_version' => '2.0'
            ];
            file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Export metadata: " . basename($metadataFile) . "\n";
        }

    } catch (Exception $e) {
        echo "✗ Error exporting {$formInfo['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Export Complete ===\n";
if (!$DRY_RUN) {
    echo "Files saved to: $exportDir\n";
}

/**
 * Export complete form processor data
 */
function exportFormProcessorData($formId)
{
    try {
        // Use the FormProcessor extension's export functionality
        $exporter = new \Civi\FormProcessor\Exporter\ExportToJson();
        $formData = $exporter->export($formId);

        return $formData; // This returns an array, not JSON string
    } catch (Exception $e) {
        echo "Error: Could not export form processor data: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Convert form data for target environment
 */
function convertFormForEnvironment($formData, $targetEnvironment)
{
    $converted = $formData;

    // Get environment-specific mappings
    $mappings = getEnvironmentMappings($targetEnvironment);

    // Convert URLs
    if (!empty($converted['calculations_configuration_url'])) {
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
 * Get environment-specific ID mappings
 */
function getEnvironmentMappings($targetEnvironment)
{
    $currentEnv = detectCurrentEnvironment();

    if ($currentEnv === 'dev' && $targetEnvironment === 'prod') {
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
    } elseif ($currentEnv === 'prod' && $targetEnvironment === 'dev') {
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
        // Same environment or unknown - no conversion needed
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
