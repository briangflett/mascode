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
        $ruleFile = $rulesDir . '/' . $rule['name'] . '.json';
        file_put_contents($ruleFile, json_encode($ruleExport, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ Rule exported: " . basename($ruleFile) . "\n";

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
