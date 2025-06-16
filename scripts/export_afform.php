<?php

/**
 * Afform Export Tool - Unified Version
 *
 * Exports CiviCRM Afforms from the database and converts them for target environment.
 * Supports exporting to any environment (dev/prod) with automatic ID mapping and URL conversion.
 *
 * USAGE:
 *   cv scr scripts/export_afform.php --user=brian.flett@masadvise.org
 *
 * CONFIGURATION (edit the variables below):
 *
 * $FORM_TO_EXPORT:
 *   - Set to the name of a specific form to export (e.g., 'afformMASRCSForm')
 *   - Only used when $EXPORT_ALL is false
 *   - Form names are case-sensitive and must match exactly
 *
 * $EXPORT_ALL:
 *   - true:  Exports ALL custom forms (excludes core CiviCRM forms starting with 'civicrm')
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
 * 1. List all available custom Afforms for reference
 * 2. Export the specified form(s) from the CiviCRM database
 * 3. Apply environment-specific conversions (IDs, URLs)
 * 4. Save to the Afforms directory with conversion metadata
 * 5. Create export logs for tracking changes
 *
 * @author MAS Team
 * @version 2.0 (Unified Export with Environment Conversion)
 * @requires CiviCRM 6.1+, Afform extension
 */

echo "=== Afform Export Tool (Unified) ===\n\n";

// CONFIGURATION
$FORM_TO_EXPORT = 'afformMASRCSForm';  // Change this to export different forms
$EXPORT_ALL = false;                   // Set to true to export all forms
$TARGET_ENVIRONMENT = 'current';       // 'dev', 'prod', or 'current'
$LIST_ONLY = false;                    // Set to true to just list available forms
$DRY_RUN = false;                      // Set to true to preview export

// Validate target environment
if (!in_array($TARGET_ENVIRONMENT, ['dev', 'prod', 'current'])) {
    echo "Error: TARGET_ENVIRONMENT must be 'dev', 'prod', or 'current'\n";
    exit(1);
}

$currentEnv = detectCurrentEnvironment();
echo "Current environment: $currentEnv\n";
echo "Target environment: $TARGET_ENVIRONMENT\n";

if ($DRY_RUN) {
    echo "*** DRY RUN MODE - No files will be created ***\n";
}
echo "\n";

try {
    // Get available forms with "MAS" in name or title
    $forms = \Civi\Api4\Afform::get()
        ->addWhere('name', 'LIKE', '%MAS%')
        ->addSelect('name', 'title', 'type')
        ->execute();
} catch (Exception $e) {
    echo "Error fetching Afforms: " . $e->getMessage() . "\n";
    echo "Make sure the Afform extension is installed and enabled.\n";
    exit(1);
}

if (empty($forms)) {
    echo "No MAS Afforms found!\n";
    exit(1);
}

echo "Available MAS Afforms:\n";
foreach ($forms as $form) {
    echo "  - {$form['name']} ({$form['title']}) [{$form['type']}]\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($forms) . " MAS forms/blocks available.\n";
    echo "To export, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine forms to export
$formsToExport = [];
if ($EXPORT_ALL) {
    echo "\nExporting ALL MAS forms and blocks...\n";
    foreach ($forms as $form) {
        $formsToExport[] = $form['name'];
    }
} else {
    echo "\nExporting form: {$FORM_TO_EXPORT}\n";
    $formFound = false;
    foreach ($forms as $form) {
        if ($form['name'] === $FORM_TO_EXPORT) {
            $formsToExport[] = $form['name'];
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
$exportDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/Afforms');
if (!$DRY_RUN && !is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
    echo "Created directory: $exportDir\n";
}

// Export each form
foreach ($formsToExport as $formName) {
    echo "\n--- " . ($DRY_RUN ? 'Preview' : 'Exporting') . " Form: $formName ---\n";

    try {
        // Get complete form data
        $form = \Civi\Api4\Afform::get()
            ->addWhere('name', '=', $formName)
            ->execute()
            ->first();

        if (!$form) {
            echo "✗ Form not found: $formName\n";
            continue;
        }

        // Apply environment conversion
        $convertedForm = $form;
        if ($TARGET_ENVIRONMENT !== 'current' && $TARGET_ENVIRONMENT !== $currentEnv) {
            echo "Converting for $TARGET_ENVIRONMENT environment...\n";
            $convertedForm = convertAfformForEnvironment($form, $currentEnv, $TARGET_ENVIRONMENT);
        }

        if ($DRY_RUN) {
            echo "✓ Form data loaded and converted\n";
            echo "✓ Would save to: {$exportDir}/{$formName}.get.json\n";
        } else {
            // Export complete form data
            $getFile = $exportDir . '/' . $formName . '.get.json';
            file_put_contents($getFile, json_encode($convertedForm, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Complete form data: " . basename($getFile) . "\n";

            // Create ID mappings
            $mappings = createAfformIdMappings($form, $convertedForm, $currentEnv, $TARGET_ENVIRONMENT);
            $mappingsFile = $exportDir . '/' . $formName . '.mappings.json';
            file_put_contents($mappingsFile, json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ ID mappings: " . basename($mappingsFile) . "\n";

            // Create export metadata
            $metadataFile = $exportDir . '/' . $formName . '.export.log';
            $metadata = [
                'exported_date' => date('Y-m-d H:i:s'),
                'source_environment' => $currentEnv,
                'target_environment' => $TARGET_ENVIRONMENT,
                'form_name' => $formName,
                'form_title' => $form['title'] ?? 'Unknown',
                'form_type' => $form['type'] ?? 'Unknown',
                'export_version' => '2.0'
            ];
            file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Export metadata: " . basename($metadataFile) . "\n";
        }

    } catch (Exception $e) {
        echo "✗ Error exporting $formName: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Export Complete ===\n";
if (!$DRY_RUN) {
    echo "Files saved to: $exportDir\n";
}

/**
 * Convert Afform data for target environment
 */
function convertAfformForEnvironment($form, $sourceEnv, $targetEnv)
{
    $converted = $form;

    // Get environment-specific mappings
    $mappings = getAfformEnvironmentMappings($sourceEnv, $targetEnv);

    // Convert email confirmation template ID
    if (!empty($converted['email_confirmation_template_id'])) {
        $converted['email_confirmation_template_id'] = convertAfformId(
            $converted['email_confirmation_template_id'],
            'email_confirmation_template_id',
            $mappings
        );
    }

    // Convert layout IDs
    if (!empty($converted['layout'])) {
        $converted['layout'] = convertAfformLayoutIds($converted['layout'], $mappings);
    }

    return $converted;
}

/**
 * Recursively convert IDs in layout structure
 */
function convertAfformLayoutIds($layout, $mappings)
{
    if (!is_array($layout)) {
        return $layout;
    }

    foreach ($layout as &$element) {
        if (is_array($element)) {
            // Check for case_type_id in data
            if (isset($element['data']['case_type_id'])) {
                $newId = convertAfformId($element['data']['case_type_id'], 'case_type_id', $mappings);
                if ($newId !== $element['data']['case_type_id']) {
                    echo "  → Converted case_type_id: {$element['data']['case_type_id']} → $newId\n";
                    $element['data']['case_type_id'] = $newId;
                }
            }

            // Check for defn with afform_default values
            if (isset($element['defn']['afform_default'])) {
                $value = $element['defn']['afform_default'];
                $name = $element['name'] ?? '';

                $newValue = convertAfformId($value, $name, $mappings);
                if ($newValue !== $value) {
                    echo "  → Converted $name: $value → $newValue\n";
                    $element['defn']['afform_default'] = $newValue;
                }
            }

            // Recursively check children
            if (isset($element['#children'])) {
                $element['#children'] = convertAfformLayoutIds($element['#children'], $mappings);
            }
        }
    }

    return $layout;
}

/**
 * Convert individual ID based on field name and mappings
 */
function convertAfformId($value, $fieldName, $mappings)
{
    // Skip non-numeric values
    if (!is_numeric($value)) {
        return $value;
    }

    $numericValue = (int)$value;

    // Map based on field patterns
    if (preg_match('/message_template|email_confirmation_template/', $fieldName) && isset($mappings['message_templates'][$numericValue])) {
        return $mappings['message_templates'][$numericValue];
    }

    if (preg_match('/case_type/', $fieldName) && isset($mappings['case_types'][$numericValue])) {
        return $mappings['case_types'][$numericValue];
    }

    if ($fieldName === 'country_id' && isset($mappings['countries'][$numericValue])) {
        return $mappings['countries'][$numericValue];
    }

    if ($fieldName === 'state_province_id' && isset($mappings['state_provinces'][$numericValue])) {
        return $mappings['state_provinces'][$numericValue];
    }

    if ($fieldName === 'location_type_id' && isset($mappings['location_types'][$numericValue])) {
        return $mappings['location_types'][$numericValue];
    }

    if ($fieldName === 'phone_type_id' && isset($mappings['phone_types'][$numericValue])) {
        return $mappings['phone_types'][$numericValue];
    }

    if ($fieldName === 'website_type_id' && isset($mappings['website_types'][$numericValue])) {
        return $mappings['website_types'][$numericValue];
    }

    return $value;
}

/**
 * Get environment-specific ID mappings for Afforms
 */
function getAfformEnvironmentMappings($sourceEnv, $targetEnv)
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
            'countries' => [],
            'state_provinces' => [],
            'case_types' => [],
            'message_templates' => []
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
            'countries' => [],
            'state_provinces' => [],
            'case_types' => [],
            'message_templates' => []
        ];
    } else {
        // Same environment - no conversion needed
        return [
            'location_types' => [],
            'phone_types' => [],
            'countries' => [],
            'state_provinces' => [],
            'case_types' => [],
            'message_templates' => []
        ];
    }
}

/**
 * Create ID to name mappings for Afform data
 */
function createAfformIdMappings($originalForm, $convertedForm, $sourceEnv, $targetEnv)
{
    $mappings = [
        'source_environment' => $sourceEnv,
        'target_environment' => $targetEnv,
        'conversions' => [],
        'message_templates' => [],
        'case_types' => [],
        'countries' => [],
        'state_provinces' => [],
        'location_types' => [],
        'phone_types' => [],
        'website_types' => []
    ];

    // Extract and map any IDs that were converted
    $ids = extractIdsFromAfform($originalForm);

    // Get message template names
    if (!empty($ids['message_template_ids'])) {
        try {
            $templates = \Civi\Api4\MessageTemplate::get()
                ->addWhere('id', 'IN', $ids['message_template_ids'])
                ->addSelect('id', 'msg_title')
                ->execute();
            foreach ($templates as $template) {
                $mappings['message_templates'][$template['id']] = $template['msg_title'];
            }
        } catch (Exception $e) {
            echo "Warning: Could not fetch message templates: " . $e->getMessage() . "\n";
        }
    }

    // Get case type names
    if (!empty($ids['case_type_ids'])) {
        try {
            $caseTypes = \Civi\Api4\CaseType::get()
                ->addWhere('id', 'IN', $ids['case_type_ids'])
                ->addSelect('id', 'name')
                ->execute();
            foreach ($caseTypes as $caseType) {
                $mappings['case_types'][$caseType['id']] = $caseType['name'];
            }
        } catch (Exception $e) {
            echo "Warning: Could not fetch case types: " . $e->getMessage() . "\n";
        }
    }

    return $mappings;
}

/**
 * Extract all ID references from Afform data
 */
function extractIdsFromAfform($form)
{
    $ids = [
        'message_template_ids' => [],
        'case_type_ids' => [],
        'country_ids' => [],
        'state_province_ids' => [],
        'location_type_ids' => [],
        'phone_type_ids' => [],
        'website_type_ids' => []
    ];

    // Check top-level fields
    if (isset($form['email_confirmation_template_id'])) {
        $ids['message_template_ids'][] = $form['email_confirmation_template_id'];
    }

    // Recursively search layout for ID references
    if (isset($form['layout'])) {
        extractIdsFromAfformLayout($form['layout'], $ids);
    }

    // Remove duplicates and empty values
    foreach ($ids as $key => $values) {
        $ids[$key] = array_unique(array_filter($values));
    }

    return $ids;
}

/**
 * Recursively extract IDs from layout structure
 */
function extractIdsFromAfformLayout($layout, &$ids)
{
    if (!is_array($layout)) {
        return;
    }

    foreach ($layout as $element) {
        if (is_array($element)) {
            // Check for case_type_id in data
            if (isset($element['data']['case_type_id'])) {
                $ids['case_type_ids'][] = $element['data']['case_type_id'];
            }

            // Check for defn with afform_default values
            if (isset($element['defn']['afform_default'])) {
                $value = $element['defn']['afform_default'];
                $name = $element['name'] ?? '';

                if ($name === 'country_id') {
                    $ids['country_ids'][] = $value;
                } elseif ($name === 'state_province_id') {
                    $ids['state_province_ids'][] = $value;
                } elseif ($name === 'location_type_id') {
                    $ids['location_type_ids'][] = $value;
                } elseif ($name === 'phone_type_id') {
                    $ids['phone_type_ids'][] = $value;
                } elseif ($name === 'website_type_id') {
                    $ids['website_type_ids'][] = $value;
                }
            }

            // Recursively check children
            if (isset($element['#children'])) {
                extractIdsFromAfformLayout($element['#children'], $ids);
            }
        }
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
