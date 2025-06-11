<?php

/**
 * Afform Export Tool
 *
 * Exports CiviCRM Afforms (FormBuilder forms) for deployment between environments.
 * Creates complete form data and ID mappings for cross-environment compatibility.
 *
 * USAGE:
 *   cv scr scripts/export_afform.php
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
 * $LIST_ONLY:
 *   - true:  Only lists available forms and exits (no export)
 *   - false: Normal export behavior
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. List all available custom forms for reference
 * 2. Export the specified form(s) to the 'ang/' directory in your extension
 * 3. Export forms and blocks based on having "MAS" in their title
 * 4. Create .get.json (complete form data) and .mappings.json (ID mappings) files
 *
 * @author MAS Team
 * @version 3.0 (Complete API Export with ID Mappings)
 * @requires CiviCRM 6.1+, Afform extension
 */

echo "=== Afform Export Tool ===\n\n";

// CONFIGURATION
$FORM_TO_EXPORT = 'afformMASRCSForm';  // Change this to export different forms
$EXPORT_ALL = false;                    // Set to true to export all forms
$LIST_ONLY = false;                    // Set to true to just list available forms

// Get available forms and blocks with "MAS" in title
try {
    $forms = \Civi\Api4\Afform::get(false)
        ->addWhere('name', 'LIKE', '%MAS%')
        ->addSelect('name', 'title', 'type')
        ->execute();
} catch (Exception $e) {
    echo "Error fetching forms: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($forms)) {
    echo "No MAS forms found!\n";
    exit(1);
}

echo "Available MAS forms and blocks:\n";
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
$exportDir = \CRM_Mascode_ExtensionUtil::path('ang');
if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
    echo "Created directory: $exportDir\n";
}

// Export each form
foreach ($formsToExport as $formName) {
    echo "\n--- Exporting Form: $formName ---\n";

    try {
        $form = \Civi\Api4\Afform::get(false)
            ->addWhere('name', '=', $formName)
            ->setCheckPermissions(false)
            ->execute()
            ->first();

        if (!$form) {
            echo "✗ Form not found: $formName\n";
            continue;
        }

        // Export complete form data
        $getFile = $exportDir . '/' . $formName . '.get.json';
        file_put_contents($getFile, json_encode($form, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ Complete form data: " . basename($getFile) . "\n";

        // Create ID mappings
        $mappings = createIdMappings($form);
        $mappingsFile = $exportDir . '/' . $formName . '.mappings.json';
        file_put_contents($mappingsFile, json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ ID mappings: " . basename($mappingsFile) . "\n";

    } catch (Exception $e) {
        echo "✗ Error exporting $formName: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Export Complete ===\n";
echo "Files saved to: $exportDir\n";

/**
 * Create ID to name mappings for form data
 */
function createIdMappings($form)
{
    $mappings = [
        'message_templates' => [],
        'case_types' => [],
        'countries' => [],
        'state_provinces' => [],
        'location_types' => [],
        'phone_types' => [],
        'website_types' => []
    ];

    // Extract IDs from form data
    $ids = extractIdsFromForm($form);

    // Get message template names
    if (!empty($ids['message_template_ids'])) {
        try {
            $templates = \Civi\Api4\MessageTemplate::get(false)
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
            $caseTypes = \Civi\Api4\CaseType::get(false)
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

    // Get country names
    if (!empty($ids['country_ids'])) {
        try {
            $countries = \Civi\Api4\Country::get(false)
                ->addWhere('id', 'IN', $ids['country_ids'])
                ->addSelect('id', 'name')
                ->execute();
            foreach ($countries as $country) {
                $mappings['countries'][$country['id']] = $country['name'];
            }
        } catch (Exception $e) {
            echo "Warning: Could not fetch countries: " . $e->getMessage() . "\n";
        }
    }

    // Get state/province names
    if (!empty($ids['state_province_ids'])) {
        try {
            $stateProvinces = \Civi\Api4\StateProvince::get(false)
                ->addWhere('id', 'IN', $ids['state_province_ids'])
                ->addSelect('id', 'name')
                ->execute();
            foreach ($stateProvinces as $stateProvince) {
                $mappings['state_provinces'][$stateProvince['id']] = $stateProvince['name'];
            }
        } catch (Exception $e) {
            echo "Warning: Could not fetch state/provinces: " . $e->getMessage() . "\n";
        }
    }

    // Get location type names
    if (!empty($ids['location_type_ids'])) {
        try {
            $locationTypes = \Civi\Api4\LocationType::get(false)
                ->addWhere('id', 'IN', $ids['location_type_ids'])
                ->addSelect('id', 'name')
                ->execute();
            foreach ($locationTypes as $locationType) {
                $mappings['location_types'][$locationType['id']] = $locationType['name'];
            }
        } catch (Exception $e) {
            echo "Warning: Could not fetch location types: " . $e->getMessage() . "\n";
        }
    }

    // Get phone type names
    if (!empty($ids['phone_type_ids'])) {
        try {
            $phoneTypes = \Civi\Api4\OptionValue::get(false)
                ->addWhere('option_group_id:name', '=', 'phone_type')
                ->addWhere('value', 'IN', $ids['phone_type_ids'])
                ->addSelect('value', 'name')
                ->execute();
            foreach ($phoneTypes as $phoneType) {
                $mappings['phone_types'][$phoneType['value']] = $phoneType['name'];
            }
        } catch (Exception $e) {
            echo "Warning: Could not fetch phone types: " . $e->getMessage() . "\n";
        }
    }

    // Get website type names
    if (!empty($ids['website_type_ids'])) {
        try {
            $websiteTypes = \Civi\Api4\OptionValue::get(false)
                ->addWhere('option_group_id:name', '=', 'website_type')
                ->addWhere('value', 'IN', $ids['website_type_ids'])
                ->addSelect('value', 'name')
                ->execute();
            foreach ($websiteTypes as $websiteType) {
                $mappings['website_types'][$websiteType['value']] = $websiteType['name'];
            }
        } catch (Exception $e) {
            echo "Warning: Could not fetch website types: " . $e->getMessage() . "\n";
        }
    }

    return $mappings;
}

/**
 * Extract all ID references from form data
 */
function extractIdsFromForm($form)
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
        extractIdsFromLayout($form['layout'], $ids);
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
function extractIdsFromLayout($layout, &$ids)
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
                extractIdsFromLayout($element['#children'], $ids);
            }
        }
    }
}
