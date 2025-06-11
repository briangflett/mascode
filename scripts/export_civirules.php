<?php

/**
 * CiviRules Export Tool
 *
 * Exports CiviRules rules and their custom actions, conditions, and triggers for deployment
 * between development, staging, and production environments.
 *
 * USAGE:
 *   cv scr scripts/export_civirules.php
 *
 * CONFIGURATION (edit the variables below):
 *
 * $RULE_TO_EXPORT:
 *   - Set to the name of a specific rule to export (e.g., 'mas_create_project_from_sr')
 *   - Only used when $EXPORT_ALL is false
 *   - Rule names are case-sensitive and must match exactly
 *
 * $EXPORT_ALL:
 *   - true:  Exports ALL rules starting with 'mas_' prefix
 *   - false: Exports only the rule specified in $RULE_TO_EXPORT
 *
 * $LIST_ONLY:
 *   - true:  Only lists available rules and exits (no export)
 *   - false: Normal export behavior
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. List all available MAS rules for reference
 * 2. Export the specified rule(s) to appropriate directories in Civi/Mascode/CiviRules/
 * 3. Scan each rule for custom actions, conditions, and triggers
 * 4. Export custom components to their respective JSON files
 * 5. Update the main JSON files (actions.json, conditions.json, triggers.json)
 *
 * OUTPUT FILES:
 *
 * Rules are exported to:
 *   - Civi/Mascode/CiviRules/rules/{rulename}.json - Complete rule definition
 *
 * Custom components update:
 *   - Civi/Mascode/CiviRules/actions.json    - Updated with any new custom actions
 *   - Civi/Mascode/CiviRules/conditions.json - Updated with any new custom conditions
 *   - Civi/Mascode/CiviRules/triggers.json   - Updated with any new custom triggers
 *
 * EXAMPLES:
 *
 * List available rules:
 *   $LIST_ONLY = true;
 *   Result: Shows all MAS rules and exits
 *
 * Export single rule:
 *   $RULE_TO_EXPORT = 'mas_create_project_from_sr';
 *   $EXPORT_ALL = false;
 *   Result: Exports the specified rule and updates component JSON files
 *
 * Export all MAS rules:
 *   $EXPORT_ALL = true;
 *   Result: Exports every rule starting with 'mas_' and all components
 *
 * NOTES:
 *
 * - Only exports rules starting with 'mas_' (MAS-specific rules)
 * - Custom components are detected by 'mas_' or 'Civi\\Mascode\\' patterns
 * - The script merges new components with existing JSON files
 * - Core CiviRules components are not exported
 * - Safe to run multiple times (updates/merges existing files)
 *
 * ERROR HANDLING:
 *
 * - If a rule is not found, it will be skipped with an error message
 * - If components are not found, they'll be noted but export continues
 * - Invalid JSON in existing files will be backed up and recreated
 *
 * @author MAS Team
 * @version 1.0
 * @requires CiviCRM 6.1+, CiviRules extension
 */

// scripts/export_civirules.php
// Exports CiviRules rules and their custom components

echo "=== CiviRules Export Tool ===\n\n";

// CONFIGURATION
$RULE_TO_EXPORT = '';  // Change this to export different rules
$EXPORT_ALL = true;                              // Set to true to export all MAS rules
$LIST_ONLY = false;                               // Set to true to just list available rules

// Get available MAS rules
try {
    $rules = \Civi\Api4\CiviRulesRule::get()
        ->addSelect('id', 'name', 'label', 'description', 'is_active')
        ->setCheckPermissions(false)
        ->execute();
} catch (Exception $e) {
    echo "Error fetching rules: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($rules)) {
    echo "No MAS rules found!\n";
    exit(1);
}

echo "Available MAS rules:\n";
foreach ($rules as $rule) {
    $status = $rule['is_active'] ? 'Active' : 'Inactive';
    echo "  - {$rule['name']} ({$rule['label']}) [$status]\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($rules) . " MAS rules available.\n";
    echo "To export, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine rules to export
$rulesToExport = [];
if ($EXPORT_ALL) {
    echo "\nExporting ALL MAS rules...\n";
    foreach ($rules as $rule) {
        $rulesToExport[] = $rule;
    }
} else {
    echo "\nExporting rule: {$RULE_TO_EXPORT}\n";
    $ruleFound = false;
    foreach ($rules as $rule) {
        if ($rule['name'] === $RULE_TO_EXPORT) {
            $rulesToExport[] = $rule;
            $ruleFound = true;
            break;
        }
    }

    if (!$ruleFound) {
        echo "Error: Rule '{$RULE_TO_EXPORT}' not found.\n";
        echo "Available rules are listed above.\n";
        exit(1);
    }
}

// Create export directories
$baseDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/CiviRules');
$rulesDir = $baseDir . '/rules';

if (!is_dir($rulesDir)) {
    if (!mkdir($rulesDir, 0755, true)) {
        echo "Error: Could not create directory: $rulesDir\n";
        exit(1);
    }
    echo "Created directory: $rulesDir\n";
}

// Track custom components to update JSON files
$customActions = [];
$customConditions = [];
$customTriggers = [];

// Export each rule
foreach ($rulesToExport as $rule) {
    echo "\n--- Exporting Rule: {$rule['name']} ---\n";

    try {
        // Get complete rule data
        $ruleData = \Civi\Api4\CiviRulesRule::get()
            ->addWhere('id', '=', $rule['id'])
            ->setCheckPermissions(false)
            ->execute()
            ->first();

        // Get rule actions
        $ruleActions = \Civi\Api4\CiviRulesRuleAction::get()
            ->addWhere('rule_id', '=', $rule['id'])
            ->setCheckPermissions(false)
            ->execute();

        // Get rule conditions
        $ruleConditions = \Civi\Api4\CiviRulesRuleCondition::get()
            ->addWhere('rule_id', '=', $rule['id'])
            ->setCheckPermissions(false)
            ->execute();

        // Get rule trigger
        $ruleTrigger = null;
        if (!empty($ruleData['trigger_id'])) {
            $ruleTrigger = \Civi\Api4\CiviRulesTrigger::get()
                ->addWhere('id', '=', $ruleData['trigger_id'])
                ->setCheckPermissions(false)
                ->execute()
                ->first();
        }

        // Analyze components for custom MAS components
        foreach ($ruleActions as $action) {
            if (!empty($action['action_id'])) {
                $actionDetails = \Civi\Api4\CiviRulesAction::get()
                    ->addWhere('id', '=', $action['action_id'])
                    ->setCheckPermissions(false)
                    ->execute()
                    ->first();

                if ($actionDetails && isCustomMasComponent($actionDetails)) {
                    $customActions[] = $actionDetails;
                    echo "Found custom action: {$actionDetails['name']}\n";
                }
            }
        }

        foreach ($ruleConditions as $condition) {
            if (!empty($condition['condition_id'])) {
                $conditionDetails = \Civi\Api4\CiviRulesCondition::get()
                    ->addWhere('id', '=', $condition['condition_id'])
                    ->setCheckPermissions(false)
                    ->execute()
                    ->first();

                if ($conditionDetails && isCustomMasComponent($conditionDetails)) {
                    $customConditions[] = $conditionDetails;
                    echo "Found custom condition: {$conditionDetails['name']}\n";
                }
            }
        }

        if ($ruleTrigger && isCustomMasComponent($ruleTrigger)) {
            $customTriggers[] = $ruleTrigger;
            echo "Found custom trigger: {$ruleTrigger['name']}\n";
        }

        // Prepare complete rule export
        $ruleExport = [
            'rule' => $ruleData,
            'actions' => $ruleActions->getArrayCopy(),
            'conditions' => $ruleConditions->getArrayCopy(),
            'trigger' => $ruleTrigger,
            'exported_date' => date('Y-m-d H:i:s'),
            'exported_by' => 'MAS Export Script',
        ];

        // Export rule to JSON file
        $ruleFile = $rulesDir . '/' . $rule['name'] . '.get.json';
        file_put_contents($ruleFile, json_encode($ruleExport, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ Rule exported: " . basename($ruleFile) . "\n";

        // Create ID mappings for this rule
        $mappings = createCiviRulesIdMappings($ruleExport);
        $mappingsFile = $rulesDir . '/' . $rule['name'] . '.mappings.json';
        file_put_contents($mappingsFile, json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ ID mappings: " . basename($mappingsFile) . "\n";

    } catch (Exception $e) {
        echo "✗ Error exporting rule {$rule['name']}: " . $e->getMessage() . "\n";
    }
}

// Update component JSON files
if (!empty($customActions) || !empty($customConditions) || !empty($customTriggers)) {
    echo "\n--- Updating Component JSON Files ---\n";

    // Update actions.json
    if (!empty($customActions)) {
        updateComponentJsonFile($baseDir . '/actions.json', $customActions, 'actions');
    }

    // Update conditions.json
    if (!empty($customConditions)) {
        updateComponentJsonFile($baseDir . '/conditions.json', $customConditions, 'conditions');
    }

    // Update triggers.json
    if (!empty($customTriggers)) {
        updateComponentJsonFile($baseDir . '/triggers.json', $customTriggers, 'triggers');
    }
} else {
    echo "\nNo custom components found to update.\n";
}

echo "\n=== Export Complete ===\n";
echo "Files saved to: $baseDir\n";

/**
 * Create ID to name mappings for CiviRules data
 */
function createCiviRulesIdMappings($ruleExport)
{
    $mappings = [
        'triggers' => [],
        'actions' => [],
        'conditions' => [],
        'contact_types' => [],
        'case_types' => [],
        'activity_types' => [],
        'relationship_types' => [],
        'custom_groups' => [],
        'custom_fields' => [],
        'option_groups' => [],
        'option_values' => []
    ];

    // Map trigger
    if (!empty($ruleExport['trigger']['id'])) {
        try {
            $trigger = \Civi\Api4\CiviRulesTrigger::get(false)
                ->addWhere('id', '=', $ruleExport['trigger']['id'])
                ->addSelect('id', 'name')
                ->execute()
                ->first();
            if ($trigger) {
                $mappings['triggers'][$trigger['id']] = $trigger['name'];
            }
        } catch (Exception $e) {
            echo "Warning: Could not fetch trigger mapping: " . $e->getMessage() . "\n";
        }
    }

    // Map actions
    foreach ($ruleExport['actions'] as $ruleAction) {
        if (!empty($ruleAction['action_id'])) {
            try {
                $action = \Civi\Api4\CiviRulesAction::get(false)
                    ->addWhere('id', '=', $ruleAction['action_id'])
                    ->addSelect('id', 'name')
                    ->execute()
                    ->first();
                if ($action) {
                    $mappings['actions'][$action['id']] = $action['name'];
                }
            } catch (Exception $e) {
                echo "Warning: Could not fetch action mapping: " . $e->getMessage() . "\n";
            }
        }

        // Extract IDs from action parameters
        if (!empty($ruleAction['action_params'])) {
            $params = is_string($ruleAction['action_params']) ? 
                json_decode($ruleAction['action_params'], true) : 
                $ruleAction['action_params'];
            if (is_array($params)) {
                extractIdsFromParams($params, $mappings);
            }
        }
    }

    // Map conditions
    foreach ($ruleExport['conditions'] as $ruleCondition) {
        if (!empty($ruleCondition['condition_id'])) {
            try {
                $condition = \Civi\Api4\CiviRulesCondition::get(false)
                    ->addWhere('id', '=', $ruleCondition['condition_id'])
                    ->addSelect('id', 'name')
                    ->execute()
                    ->first();
                if ($condition) {
                    $mappings['conditions'][$condition['id']] = $condition['name'];
                }
            } catch (Exception $e) {
                echo "Warning: Could not fetch condition mapping: " . $e->getMessage() . "\n";
            }
        }

        // Extract IDs from condition parameters
        if (!empty($ruleCondition['condition_params'])) {
            $params = is_string($ruleCondition['condition_params']) ? 
                json_decode($ruleCondition['condition_params'], true) : 
                $ruleCondition['condition_params'];
            if (is_array($params)) {
                extractIdsFromParams($params, $mappings);
            }
        }
    }

    // Extract IDs from trigger parameters
    if (!empty($ruleExport['rule']['trigger_params'])) {
        $params = is_string($ruleExport['rule']['trigger_params']) ? 
            json_decode($ruleExport['rule']['trigger_params'], true) : 
            $ruleExport['rule']['trigger_params'];
        if (is_array($params)) {
            extractIdsFromParams($params, $mappings);
        }
    }

    return $mappings;
}

/**
 * Extract IDs from parameters and populate mappings
 */
function extractIdsFromParams($params, &$mappings)
{
    foreach ($params as $key => $value) {
        if (is_array($value)) {
            extractIdsFromParams($value, $mappings);
            continue;
        }

        if (!is_numeric($value)) {
            continue;
        }

        // Map common parameter patterns to their entity types
        if (preg_match('/contact_type|contact_sub_type/', $key)) {
            mapContactType($value, $mappings);
        } elseif (preg_match('/case_type/', $key)) {
            mapCaseType($value, $mappings);
        } elseif (preg_match('/activity_type/', $key)) {
            mapActivityType($value, $mappings);
        } elseif (preg_match('/relationship_type/', $key)) {
            mapRelationshipType($value, $mappings);
        } elseif (preg_match('/custom_field|custom_/', $key)) {
            mapCustomField($value, $mappings);
        } elseif (preg_match('/option_group/', $key)) {
            mapOptionGroup($value, $mappings);
        } elseif (preg_match('/option_value/', $key)) {
            mapOptionValue($value, $mappings);
        }
    }
}

/**
 * Map contact type ID to name
 */
function mapContactType($id, &$mappings)
{
    if (isset($mappings['contact_types'][$id])) return;
    
    try {
        $contactType = \Civi\Api4\ContactType::get(false)
            ->addWhere('id', '=', $id)
            ->addSelect('id', 'name')
            ->execute()
            ->first();
        if ($contactType) {
            $mappings['contact_types'][$contactType['id']] = $contactType['name'];
        }
    } catch (Exception $e) {
        // Ignore mapping errors
    }
}

/**
 * Map case type ID to name
 */
function mapCaseType($id, &$mappings)
{
    if (isset($mappings['case_types'][$id])) return;
    
    try {
        $caseType = \Civi\Api4\CaseType::get(false)
            ->addWhere('id', '=', $id)
            ->addSelect('id', 'name')
            ->execute()
            ->first();
        if ($caseType) {
            $mappings['case_types'][$caseType['id']] = $caseType['name'];
        }
    } catch (Exception $e) {
        // Ignore mapping errors
    }
}

/**
 * Map activity type ID to name
 */
function mapActivityType($id, &$mappings)
{
    if (isset($mappings['activity_types'][$id])) return;
    
    try {
        $activityType = \Civi\Api4\OptionValue::get(false)
            ->addWhere('option_group_id:name', '=', 'activity_type')
            ->addWhere('value', '=', $id)
            ->addSelect('value', 'name')
            ->execute()
            ->first();
        if ($activityType) {
            $mappings['activity_types'][$activityType['value']] = $activityType['name'];
        }
    } catch (Exception $e) {
        // Ignore mapping errors
    }
}

/**
 * Map relationship type ID to name
 */
function mapRelationshipType($id, &$mappings)
{
    if (isset($mappings['relationship_types'][$id])) return;
    
    try {
        $relationshipType = \Civi\Api4\RelationshipType::get(false)
            ->addWhere('id', '=', $id)
            ->addSelect('id', 'name_a_b')
            ->execute()
            ->first();
        if ($relationshipType) {
            $mappings['relationship_types'][$relationshipType['id']] = $relationshipType['name_a_b'];
        }
    } catch (Exception $e) {
        // Ignore mapping errors
    }
}

/**
 * Map custom field ID to name
 */
function mapCustomField($id, &$mappings)
{
    if (isset($mappings['custom_fields'][$id])) return;
    
    try {
        $customField = \Civi\Api4\CustomField::get(false)
            ->addWhere('id', '=', $id)
            ->addSelect('id', 'name', 'custom_group_id')
            ->execute()
            ->first();
        if ($customField) {
            $mappings['custom_fields'][$customField['id']] = $customField['name'];
            // Also map the custom group
            mapCustomGroup($customField['custom_group_id'], $mappings);
        }
    } catch (Exception $e) {
        // Ignore mapping errors
    }
}

/**
 * Map custom group ID to name
 */
function mapCustomGroup($id, &$mappings)
{
    if (isset($mappings['custom_groups'][$id])) return;
    
    try {
        $customGroup = \Civi\Api4\CustomGroup::get(false)
            ->addWhere('id', '=', $id)
            ->addSelect('id', 'name')
            ->execute()
            ->first();
        if ($customGroup) {
            $mappings['custom_groups'][$customGroup['id']] = $customGroup['name'];
        }
    } catch (Exception $e) {
        // Ignore mapping errors
    }
}

/**
 * Map option group ID to name
 */
function mapOptionGroup($id, &$mappings)
{
    if (isset($mappings['option_groups'][$id])) return;
    
    try {
        $optionGroup = \Civi\Api4\OptionGroup::get(false)
            ->addWhere('id', '=', $id)
            ->addSelect('id', 'name')
            ->execute()
            ->first();
        if ($optionGroup) {
            $mappings['option_groups'][$optionGroup['id']] = $optionGroup['name'];
        }
    } catch (Exception $e) {
        // Ignore mapping errors
    }
}

/**
 * Map option value ID to name
 */
function mapOptionValue($id, &$mappings)
{
    if (isset($mappings['option_values'][$id])) return;
    
    try {
        $optionValue = \Civi\Api4\OptionValue::get(false)
            ->addWhere('id', '=', $id)
            ->addSelect('id', 'name', 'option_group_id')
            ->execute()
            ->first();
        if ($optionValue) {
            $mappings['option_values'][$optionValue['id']] = $optionValue['name'];
            // Also map the option group
            mapOptionGroup($optionValue['option_group_id'], $mappings);
        }
    } catch (Exception $e) {
        // Ignore mapping errors
    }
}

/**
 * Check if a component is a custom MAS component
 */
function isCustomMasComponent($component)
{
    if (empty($component['name']) && empty($component['class_name'])) {
        return false;
    }

    $name = $component['name'] ?? '';
    $className = $component['class_name'] ?? '';

    // Check for MAS-specific patterns
    return (
        strpos($name, 'mas_') === 0 ||
        strpos($className, 'Civi\\Mascode\\') !== false ||
        strpos($className, 'CRM_Mascode_') !== false
    );
}

/**
 * Update a component JSON file with new components
 */
function updateComponentJsonFile($filePath, $newComponents, $componentType)
{
    echo "Updating $componentType JSON file...\n";

    // Load existing file or create empty array
    $existingComponents = [];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $existingComponents = $decoded;
        } else {
            // Backup invalid file
            copy($filePath, $filePath . '.backup.' . date('Y-m-d_H-i-s'));
            echo "  Backed up invalid JSON file: " . basename($filePath) . ".backup\n";
        }
    }

    // Merge new components
    $updated = false;
    foreach ($newComponents as $component) {
        // Check if component already exists
        $exists = false;
        foreach ($existingComponents as $existing) {
            if (($existing['name'] ?? '') === ($component['name'] ?? '')) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            // Convert component to export format
            $exportComponent = convertComponentToExportFormat($component, $componentType);
            if ($exportComponent) {
                $existingComponents[] = $exportComponent;
                $updated = true;
                echo "  Added: {$component['name']}\n";
            }
        } else {
            echo "  Already exists: {$component['name']}\n";
        }
    }

    if ($updated) {
        // Sort components by name for consistency
        usort($existingComponents, function ($a, $b) {
            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });

        // Write updated file
        file_put_contents($filePath, json_encode($existingComponents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ Updated: " . basename($filePath) . "\n";
    } else {
        echo "  No updates needed for " . basename($filePath) . "\n";
    }
}

/**
 * Convert a component to export format for JSON files
 */
function convertComponentToExportFormat($component, $componentType)
{
    switch ($componentType) {
        case 'actions':
            return [
                'name' => $component['name'] ?? '',
                'label' => $component['label'] ?? '',
                'class_name' => $component['class_name'] ?? '',
            ];

        case 'conditions':
            return [
                'name' => $component['name'] ?? '',
                'label' => $component['label'] ?? '',
                'class_name' => $component['class_name'] ?? '',
            ];

        case 'triggers':
            return [
                'name' => $component['name'] ?? '',
                'label' => $component['label'] ?? '',
                'object_name' => $component['object_name'] ?? null,
                'op' => $component['op'] ?? null,
                'class_name' => $component['class_name'] ?? '',
                'cron' => $component['cron'] ?? '0',
            ];

        default:
            return null;
    }
}
