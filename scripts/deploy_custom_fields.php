<?php

/**
 * Generic Custom Fields Deployment Script
 *
 * This script reads a JSON configuration file and creates custom fields based on the configuration.
 * 
 * USAGE:
 *   cv scr scripts/deploy_custom_fields.php sasf
 *   cv scr scripts/deploy_custom_fields.php sass
 *
 * The script will look for configuration files in scripts/custom_fields/{config_name}.json
 *
 * JSON Configuration Format:
 * {
 *   "name": "Configuration Name",
 *   "description": "Description of the configuration",
 *   "environment": "prod",
 *   "custom_group": {
 *     "id": 7,
 *     "name": "Custom_Group_Name",
 *     "title": "Custom Group Title"
 *   },
 *   "option_groups": {
 *     "group_key": {
 *       "id": 119,
 *       "name": "option_group_name",
 *       "title": "Option Group Title"
 *     }
 *   },
 *   "fields": [
 *     {
 *       "name": "field_name",
 *       "label": "Field Label",
 *       "data_type": "String",
 *       "html_type": "Radio",
 *       "option_group": "group_key",
 *       "weight": 1,
 *       "is_required": false,
 *       "is_searchable": true,
 *       "is_active": true,
 *       "is_view": true
 *     }
 *   ]
 * }
 */

// ============================================================================
// ARGUMENT PROCESSING
// ============================================================================

if (empty($argv[1])) {
    echo "ERROR: Configuration name is required\n";
    echo "USAGE: cv scr scripts/deploy_custom_fields.php <config_name>\n";
    echo "Available configurations:\n";
    
    // List available configuration files
    $configDir = __DIR__ . '/custom_fields';
    if (is_dir($configDir)) {
        $files = glob($configDir . '/*.json');
        foreach ($files as $file) {
            $basename = basename($file, '.json');
            echo "  - $basename\n";
        }
    }
    exit(1);
}

$configName = $argv[1];
$configFile = __DIR__ . '/custom_fields/' . $configName . '.json';

if (!file_exists($configFile)) {
    echo "ERROR: Configuration file not found: $configFile\n";
    exit(1);
}

// ============================================================================
// CONFIGURATION LOADING
// ============================================================================

echo "=== Generic Custom Fields Deployment Script ===\n";
echo "Configuration: $configName\n";

$configJson = file_get_contents($configFile);
$config = json_decode($configJson, true);

if (!$config) {
    echo "ERROR: Invalid JSON in configuration file: $configFile\n";
    exit(1);
}

echo "Name: {$config['name']}\n";
echo "Description: {$config['description']}\n";
echo "Environment: {$config['environment']}\n";
echo "Starting deployment...\n\n";

$results = [];

try {
    // ========================================================================
    // STEP 1: VERIFY PREREQUISITES
    // ========================================================================
    
    echo "Step 1: Verifying Prerequisites...\n";

    // Verify Custom Group exists
    $customGroup = \Civi\Api4\CustomGroup::get(false)
        ->addWhere('id', '=', $config['custom_group']['id'])
        ->execute()->first();

    if (!$customGroup) {
        throw new Exception("Custom Group with ID {$config['custom_group']['id']} not found. Please create it first.");
    }

    // Verify custom group name matches if provided
    if (!empty($config['custom_group']['name']) && $customGroup['name'] !== $config['custom_group']['name']) {
        echo "WARNING: Custom Group name mismatch. Expected '{$config['custom_group']['name']}', found '{$customGroup['name']}'\n";
    }

    echo "✓ Custom Group verified: {$customGroup['title']} (ID: {$customGroup['id']})\n";

    // Verify Option Groups exist
    $optionGroupIds = [];
    foreach ($config['option_groups'] as $groupKey => $optionGroupConfig) {
        $optionGroup = \Civi\Api4\OptionGroup::get(false)
            ->addWhere('id', '=', $optionGroupConfig['id'])
            ->execute()->first();

        if (!$optionGroup) {
            throw new Exception("Option Group with ID {$optionGroupConfig['id']} not found. Please create it first.");
        }

        // Verify option group name matches if provided
        if (!empty($optionGroupConfig['name']) && $optionGroup['name'] !== $optionGroupConfig['name']) {
            echo "WARNING: Option Group name mismatch. Expected '{$optionGroupConfig['name']}', found '{$optionGroup['name']}'\n";
        }

        $optionGroupIds[$groupKey] = $optionGroupConfig['id'];
        echo "✓ Option Group verified: {$optionGroup['title']} (ID: {$optionGroup['id']})\n";
    }

    // ========================================================================
    // STEP 2: CREATE CUSTOM FIELDS
    // ========================================================================
    
    echo "\nStep 2: Creating Custom Fields...\n";

    $createdFields = 0;
    $updatedFields = 0;
    $existingFields = 0;

    foreach ($config['fields'] as $fieldConfig) {
        $fieldName = $fieldConfig['name'];
        $fieldLabel = $fieldConfig['label'];
        
        // Check if field already exists
        $existingField = \Civi\Api4\CustomField::get(false)
            ->addWhere('custom_group_id', '=', $config['custom_group']['id'])
            ->addWhere('name', '=', $fieldName)
            ->execute()->first();

        // Prepare field values
        $fieldValues = [
            'custom_group_id' => $config['custom_group']['id'],
            'name' => $fieldName,
            'label' => $fieldLabel,
            'data_type' => $fieldConfig['data_type'],
            'html_type' => $fieldConfig['html_type'],
            'weight' => $fieldConfig['weight'],
            'is_required' => $fieldConfig['is_required'],
            'is_searchable' => $fieldConfig['is_searchable'],
            'is_active' => $fieldConfig['is_active'],
            'is_view' => $fieldConfig['is_view']
        ];

        // Add option group if specified
        if (!empty($fieldConfig['option_group']) && isset($optionGroupIds[$fieldConfig['option_group']])) {
            $fieldValues['option_group_id'] = $optionGroupIds[$fieldConfig['option_group']];
        }

        if (!$existingField) {
            // Create new field
            $customField = \Civi\Api4\CustomField::create(false);
            foreach ($fieldValues as $key => $value) {
                $customField->addValue($key, $value);
            }
            $result = $customField->execute()->first();
            
            $createdFields++;
            echo "✓ Created field: $fieldName\n";
        } else {
            // Update existing field (label and other properties)
            $customField = \Civi\Api4\CustomField::update(false);
            
            // Only update certain fields to avoid breaking existing data
            $updateFields = ['label', 'weight', 'is_required', 'is_searchable', 'is_active', 'is_view'];
            foreach ($updateFields as $updateField) {
                if (isset($fieldValues[$updateField])) {
                    $customField->addValue($updateField, $fieldValues[$updateField]);
                }
            }
            
            $customField->addWhere('id', '=', $existingField['id']);
            $result = $customField->execute();
            
            if ($result->count() > 0) {
                $updatedFields++;
                echo "✓ Updated field: $fieldName\n";
            } else {
                $existingFields++;
                echo "  - Field unchanged: $fieldName\n";
            }
        }
    }

    echo "✓ Created $createdFields new fields, updated $updatedFields fields, $existingFields unchanged\n";
    $results['created_fields'] = $createdFields;
    $results['updated_fields'] = $updatedFields;
    $results['existing_fields'] = $existingFields;

    // ========================================================================
    // STEP 3: CLEAR CACHE
    // ========================================================================
    
    echo "\nStep 3: Clearing cache...\n";
    \Civi\Api4\System::flush(false)->execute();
    echo "✓ Cache cleared\n";

    // ========================================================================
    // DEPLOYMENT SUMMARY
    // ========================================================================
    
    echo "\n=== Deployment Complete ===\n";
    echo "Configuration: {$config['name']}\n";
    echo "Summary:\n";
    echo "- Custom Group ID: {$config['custom_group']['id']}\n";
    echo "- Custom Fields: {$results['created_fields']} created, {$results['updated_fields']} updated, {$results['existing_fields']} unchanged\n";
    echo "- Total Fields Processed: " . count($config['fields']) . "\n";

    echo "\nNext Steps:\n";
    echo "1. Verify the fields appear correctly in the CiviCRM interface\n";
    echo "2. Test any forms that use these custom fields\n";
    echo "3. Update any related Afform files if needed\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}