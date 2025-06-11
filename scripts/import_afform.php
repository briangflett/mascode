<?php

/**
 * Afform Import Tool
 *
 * Imports CiviCRM Afforms from exported .get.json and .mappings.json files.
 * Handles ID mapping for cross-environment compatibility.
 *
 * USAGE:
 *   cv scr scripts/import_afform.php
 *
 * CONFIGURATION (edit the variables below):
 *
 * $FORM_TO_IMPORT:
 *   - Set to the name of a specific form to import (e.g., 'afformMASRCSForm')
 *   - Only used when $IMPORT_ALL is false
 *   - Form names are case-sensitive and must match exactly
 *
 * $IMPORT_ALL:
 *   - true:  Imports ALL forms found in the 'ang/' directory
 *   - false: Imports only the form specified in $FORM_TO_IMPORT
 *
 * $LIST_ONLY:
 *   - true:  Only lists available export files and exits (no import)
 *   - false: Normal import behavior
 *
 * $DRY_RUN:
 *   - true:  Show what would be imported without making changes
 *   - false: Actually import the forms
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. List all available export files for reference
 * 2. Read form data from .get.json files
 * 3. Read ID mappings from .mappings.json files
 * 4. Convert development IDs to production IDs using the mappings
 * 5. Import forms using the Afform API
 *
 * @author MAS Team
 * @version 1.0 (Complete API Import with ID Mappings)
 * @requires CiviCRM 6.1+, Afform extension
 */

echo "=== Afform Import Tool ===\n\n";

// CONFIGURATION
$FORM_TO_IMPORT = 'afformMASRCSForm';  // Change this to import different forms
$IMPORT_ALL = false;                   // Set to true to import all forms
$LIST_ONLY = false;                   // Set to true to just list available files
$DRY_RUN = false;                     // Set to true to preview changes without importing

// Get import directory
$importDir = \CRM_Mascode_ExtensionUtil::path('ang');
if (!is_dir($importDir)) {
    echo "Error: Import directory does not exist: $importDir\n";
    echo "Run the export script first.\n";
    exit(1);
}

// Find available export files
$availableFiles = [];
$files = scandir($importDir);
foreach ($files as $file) {
    if (preg_match('/(.+)\.get\.json$/', $file, $matches)) {
        $formName = $matches[1];
        $getFile = $importDir . '/' . $file;
        $mappingsFile = $importDir . '/' . $formName . '.mappings.json';
        
        $availableFiles[$formName] = [
            'get_file' => $getFile,
            'mappings_file' => file_exists($mappingsFile) ? $mappingsFile : null
        ];
    }
}

if (empty($availableFiles)) {
    echo "No export files found in: $importDir\n";
    echo "Expected files: [formname].get.json and [formname].mappings.json\n";
    exit(1);
}

echo "Available export files:\n";
foreach ($availableFiles as $formName => $files) {
    $status = $files['mappings_file'] ? '✓ Complete' : '⚠ Missing mappings';
    echo "  - $formName ($status)\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($availableFiles) . " export files available.\n";
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
        echo "Error: Form '{$FORM_TO_IMPORT}' export files not found.\n";
        echo "Available forms are listed above.\n";
        exit(1);
    }
    $formsToImport[] = $FORM_TO_IMPORT;
}

if ($DRY_RUN) {
    echo "\n*** DRY RUN MODE - No changes will be made ***\n";
}

// Import each form
foreach ($formsToImport as $formName) {
    echo "\n--- " . ($DRY_RUN ? 'Preview' : 'Importing') . " Form: $formName ---\n";

    try {
        $files = $availableFiles[$formName];
        
        // Read form data
        $formData = json_decode(file_get_contents($files['get_file']), true);
        if (!$formData) {
            echo "✗ Error reading form data from: " . basename($files['get_file']) . "\n";
            continue;
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

        // Apply ID mappings to convert to production IDs
        $convertedFormData = applyIdMappings($formData, $mappings);
        
        if ($DRY_RUN) {
            echo "✓ Form data loaded and converted\n";
            if (!empty($mappings)) {
                echo "✓ ID mappings applied:\n";
                foreach ($mappings as $type => $mapping) {
                    if (!empty($mapping)) {
                        echo "  - $type: " . count($mapping) . " mappings\n";
                    }
                }
            }
            echo "✓ Would create/update form: $formName\n";
        } else {
            // Actually import the form
            $result = \Civi\Api4\Afform::save(false)
                ->addRecord($convertedFormData)
                ->execute();
            
            echo "✓ Form imported successfully: $formName\n";
            if (!empty($mappings)) {
                echo "✓ ID mappings applied\n";
            }
        }

    } catch (Exception $e) {
        echo "✗ Error " . ($DRY_RUN ? 'previewing' : 'importing') . " $formName: " . $e->getMessage() . "\n";
    }
}

echo "\n=== " . ($DRY_RUN ? 'Preview' : 'Import') . " Complete ===\n";

/**
 * Apply ID mappings to convert development IDs to production IDs
 */
function applyIdMappings($formData, $mappings)
{
    if (empty($mappings)) {
        return $formData;
    }

    // Convert top-level fields
    if (isset($formData['email_confirmation_template_id']) && !empty($mappings['message_templates'])) {
        $devId = $formData['email_confirmation_template_id'];
        if (isset($mappings['message_templates'][$devId])) {
            $templateName = $mappings['message_templates'][$devId];
            $prodId = lookupMessageTemplateId($templateName);
            if ($prodId) {
                $formData['email_confirmation_template_id'] = $prodId;
                echo "  → Mapped message template '$templateName': $devId → $prodId\n";
            }
        }
    }

    // Convert layout IDs
    if (isset($formData['layout'])) {
        $formData['layout'] = applyIdMappingsToLayout($formData['layout'], $mappings);
    }

    return $formData;
}

/**
 * Recursively apply ID mappings to layout structure
 */
function applyIdMappingsToLayout($layout, $mappings)
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
                    $prodId = lookupCaseTypeId($caseTypeName);
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
                $converted = false;

                if ($fieldName === 'country_id' && !empty($mappings['countries'][$devValue])) {
                    $countryName = $mappings['countries'][$devValue];
                    $prodId = lookupCountryId($countryName);
                    if ($prodId) {
                        $element['defn']['afform_default'] = (string)$prodId;
                        echo "  → Mapped country '$countryName': $devValue → $prodId\n";
                        $converted = true;
                    }
                } elseif ($fieldName === 'state_province_id' && !empty($mappings['state_provinces'][$devValue])) {
                    $provinceName = $mappings['state_provinces'][$devValue];
                    $prodId = lookupStateProvinceId($provinceName);
                    if ($prodId) {
                        $element['defn']['afform_default'] = $prodId;
                        echo "  → Mapped province '$provinceName': $devValue → $prodId\n";
                        $converted = true;
                    }
                } elseif ($fieldName === 'location_type_id' && !empty($mappings['location_types'][$devValue])) {
                    $locationTypeName = $mappings['location_types'][$devValue];
                    $prodId = lookupLocationTypeId($locationTypeName);
                    if ($prodId) {
                        $element['defn']['afform_default'] = (string)$prodId;
                        echo "  → Mapped location type '$locationTypeName': $devValue → $prodId\n";
                        $converted = true;
                    }
                } elseif ($fieldName === 'phone_type_id' && !empty($mappings['phone_types'][$devValue])) {
                    $phoneTypeName = $mappings['phone_types'][$devValue];
                    $prodId = lookupPhoneTypeId($phoneTypeName);
                    if ($prodId) {
                        $element['defn']['afform_default'] = (string)$prodId;
                        echo "  → Mapped phone type '$phoneTypeName': $devValue → $prodId\n";
                        $converted = true;
                    }
                } elseif ($fieldName === 'website_type_id' && !empty($mappings['website_types'][$devValue])) {
                    $websiteTypeName = $mappings['website_types'][$devValue];
                    $prodId = lookupWebsiteTypeId($websiteTypeName);
                    if ($prodId) {
                        $element['defn']['afform_default'] = (string)$prodId;
                        echo "  → Mapped website type '$websiteTypeName': $devValue → $prodId\n";
                        $converted = true;
                    }
                }
            }

            // Recursively process children
            if (isset($element['#children'])) {
                $element['#children'] = applyIdMappingsToLayout($element['#children'], $mappings);
            }
        }
    }

    return $layout;
}

/**
 * Lookup functions to find production IDs by name
 */
function lookupMessageTemplateId($templateName)
{
    try {
        $result = \Civi\Api4\MessageTemplate::get(false)
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

function lookupCaseTypeId($caseTypeName)
{
    try {
        $result = \Civi\Api4\CaseType::get(false)
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

function lookupCountryId($countryName)
{
    try {
        $result = \Civi\Api4\Country::get(false)
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

function lookupStateProvinceId($provinceName)
{
    try {
        $result = \Civi\Api4\StateProvince::get(false)
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

function lookupLocationTypeId($locationTypeName)
{
    try {
        $result = \Civi\Api4\LocationType::get(false)
            ->addWhere('name', '=', $locationTypeName)
            ->addSelect('id')
            ->execute()
            ->first();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        echo "Warning: Could not lookup location type '$locationTypeName': " . $e->getMessage() . "\n";
        return null;
    }
}

function lookupPhoneTypeId($phoneTypeName)
{
    try {
        $result = \Civi\Api4\OptionValue::get(false)
            ->addWhere('option_group_id:name', '=', 'phone_type')
            ->addWhere('name', '=', $phoneTypeName)
            ->addSelect('value')
            ->execute()
            ->first();
        return $result ? $result['value'] : null;
    } catch (Exception $e) {
        echo "Warning: Could not lookup phone type '$phoneTypeName': " . $e->getMessage() . "\n";
        return null;
    }
}

function lookupWebsiteTypeId($websiteTypeName)
{
    try {
        $result = \Civi\Api4\OptionValue::get(false)
            ->addWhere('option_group_id:name', '=', 'website_type')
            ->addWhere('name', '=', $websiteTypeName)
            ->addSelect('value')
            ->execute()
            ->first();
        return $result ? $result['value'] : null;
    } catch (Exception $e) {
        echo "Warning: Could not lookup website type '$websiteTypeName': " . $e->getMessage() . "\n";
        return null;
    }
}