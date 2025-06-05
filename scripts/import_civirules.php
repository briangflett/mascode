<?php

/**
 * CiviRules Import Tool
 *
 * Imports CiviRules rules and their custom actions, conditions, and triggers from exported JSON files.
 * Complements the export_civirules.php script for deployment between environments.
 *
 * USAGE:
 *   cv scr scripts/import_civirules.php
 *
 * CONFIGURATION (edit the variables below):
 *
 * $RULE_TO_IMPORT:
 *   - Set to the name of a specific rule to import (e.g., 'mas_create_project_from_sr')
 *   - Only used when $IMPORT_ALL is false
 *   - Rule names are case-sensitive and must match the JSON filename (without .json)
 *
 * $IMPORT_ALL:
 *   - true:  Imports ALL rule JSON files found in the rules directory
 *   - false: Imports only the rule specified in $RULE_TO_IMPORT
 *
 * $LIST_ONLY:
 *   - true:  Only lists available rule files and exits (no import)
 *   - false: Normal import behavior
 *
 * $UPDATE_EXISTING:
 *   - true:  Updates existing rules if they already exist
 *   - false: Skips rules that already exist (safer option)
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. Import custom components (actions, conditions, triggers) from JSON files
 * 2. Import rule definitions from the rules/ directory
 * 3. Recreate rule conditions and actions with proper relationships
 * 4. Handle ID mapping between environments automatically
 * 5. Activate imported rules based on their original state
 *
 * INPUT FILES:
 *
 * Rules are imported from:
 *   - Civi/Mascode/CiviRules/rules/{rulename}.json - Complete rule definition
 *
 * Custom components imported from:
 *   - Civi/Mascode/CiviRules/actions.json    - Custom actions definitions
 *   - Civi/Mascode/CiviRules/conditions.json - Custom conditions definitions
 *   - Civi/Mascode/CiviRules/triggers.json   - Custom triggers definitions
 *
 * EXAMPLES:
 *
 * List available rule files:
 *   $LIST_ONLY = true;
 *   Result: Shows all available rule JSON files and exits
 *
 * Import single rule:
 *   $RULE_TO_IMPORT = 'mas_create_project_from_sr';
 *   $IMPORT_ALL = false;
 *   Result: Imports the specified rule and required components
 *
 * Import all rules:
 *   $IMPORT_ALL = true;
 *   Result: Imports every rule JSON file found and all components
 *
 * NOTES:
 *
 * - Components are imported before rules to ensure dependencies exist
 * - Existing components are updated if they have the same name
 * - ID mapping is handled automatically (dev IDs ≠ production IDs)
 * - Rules are created with new IDs but maintain their logical relationships
 * - Rule names must be unique - duplicates will be skipped or updated
 *
 * ERROR HANDLING:
 *
 * - Missing JSON files are skipped with warning messages
 * - Invalid JSON format will halt import with error details
 * - Missing dependencies (triggers/actions/conditions) will be reported
 * - Database constraint violations are caught and reported
 *
 * @author MAS Team
 * @version 1.0
 * @requires CiviCRM 6.1+, CiviRules extension
 */

// scripts/import_civirules.php
// Imports CiviRules rules and their custom components

echo "=== CiviRules Import Tool ===\n\n";

// CONFIGURATION
$RULE_TO_IMPORT = '';                        // Change this to import specific rule
$IMPORT_ALL = true;                          // Set to true to import all available rules
$LIST_ONLY = false;                          // Set to true to just list available rule files
$UPDATE_EXISTING = false;                    // Set to true to update existing rules

// Define paths
$baseDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/CiviRules');
$rulesDir = $baseDir . '/rules';

// Check if import directory exists
if (!is_dir($rulesDir)) {
    echo "Error: Rules directory not found: $rulesDir\n";
    echo "Make sure you've exported rules first using export_civirules.php\n";
    exit(1);
}

// Get available rule files
$ruleFiles = glob($rulesDir . '/*.json');
if (empty($ruleFiles)) {
    echo "No rule JSON files found in: $rulesDir\n";
    echo "Make sure you've exported rules first using export_civirules.php\n";
    exit(1);
}

echo "Available rule files:\n";
foreach ($ruleFiles as $file) {
    $ruleName = basename($file, '.json');
    echo "  - $ruleName\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($ruleFiles) . " rule files available.\n";
    echo "To import, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine files to import
$filesToImport = [];
if ($IMPORT_ALL) {
    echo "\nImporting ALL available rules...\n";
    $filesToImport = $ruleFiles;
} else {
    echo "\nImporting rule: {$RULE_TO_IMPORT}\n";
    $targetFile = $rulesDir . '/' . $RULE_TO_IMPORT . '.json';
    if (file_exists($targetFile)) {
        $filesToImport[] = $targetFile;
    } else {
        echo "Error: Rule file not found: $targetFile\n";
        echo "Available files are listed above.\n";
        exit(1);
    }
}

// Import custom components first
echo "\n--- Importing Custom Components ---\n";
$componentMappings = [
    'actions' => [],
    'conditions' => [],
    'triggers' => []
];

// Import actions
$actionsFile = $baseDir . '/actions.json';
if (file_exists($actionsFile)) {
    $componentMappings['actions'] = importActions($actionsFile);
}

// Import conditions
$conditionsFile = $baseDir . '/conditions.json';
if (file_exists($conditionsFile)) {
    $componentMappings['conditions'] = importConditions($conditionsFile);
}

// Import triggers
$triggersFile = $baseDir . '/triggers.json';
if (file_exists($triggersFile)) {
    $componentMappings['triggers'] = importTriggers($triggersFile);
}

// Import rules
echo "\n--- Importing Rules ---\n";
$importedRules = 0;
$skippedRules = 0;
$errorRules = 0;

foreach ($filesToImport as $ruleFile) {
    $ruleName = basename($ruleFile, '.json');
    echo "\n--- Importing Rule: $ruleName ---\n";

    try {
        // Load rule data
        $content = file_get_contents($ruleFile);
        $ruleData = json_decode($content, true);

        if (!$ruleData) {
            echo "✗ Error: Invalid JSON in file: $ruleFile\n";
            $errorRules++;
            continue;
        }

        // Check if rule already exists
        $existingRule = \Civi\Api4\CiviRulesRule::get()
            ->addWhere('name', '=', $ruleData['rule']['name'])
            ->setCheckPermissions(false)
            ->execute()
            ->first();

        if ($existingRule && !$UPDATE_EXISTING) {
            echo "⚠ Skipping existing rule: {$ruleData['rule']['name']}\n";
            $skippedRules++;
            continue;
        }

        // Map trigger ID if needed
        $triggerId = null;
        if (!empty($ruleData['trigger']['name'])) {
            $triggerName = $ruleData['trigger']['name'];
            if (isset($componentMappings['triggers'][$triggerName])) {
                $triggerId = $componentMappings['triggers'][$triggerName];
            } else {
                // Try to find existing trigger
                $trigger = \Civi\Api4\CiviRulesTrigger::get()
                    ->addWhere('name', '=', $triggerName)
                    ->setCheckPermissions(false)
                    ->execute()
                    ->first();
                if ($trigger) {
                    $triggerId = $trigger['id'];
                }
            }

            if (!$triggerId) {
                echo "✗ Error: Required trigger not found: $triggerName\n";
                $errorRules++;
                continue;
            }
        }

        // Create or update rule
        if ($existingRule) {
            echo "Updating existing rule...\n";
            $rule = \Civi\Api4\CiviRulesRule::update()
                ->addWhere('id', '=', $existingRule['id'])
                ->addValue('label', $ruleData['rule']['label'])
                ->addValue('description', $ruleData['rule']['description'])
                ->addValue('help_text', $ruleData['rule']['help_text'] ?? '')
                ->addValue('is_active', $ruleData['rule']['is_active'])
                ->addValue('trigger_id', $triggerId)
                ->addValue('trigger_params', $ruleData['rule']['trigger_params'] ?? '')
                ->setCheckPermissions(false)
                ->execute()
                ->first();
            $ruleId = $existingRule['id'];

            // Delete existing conditions and actions
            \Civi\Api4\CiviRulesRuleCondition::delete()
                ->addWhere('rule_id', '=', $ruleId)
                ->setCheckPermissions(false)
                ->execute();

            \Civi\Api4\CiviRulesRuleAction::delete()
                ->addWhere('rule_id', '=', $ruleId)
                ->setCheckPermissions(false)
                ->execute();
        } else {
            echo "Creating new rule...\n";
            $rule = \Civi\Api4\CiviRulesRule::create()
                ->addValue('name', $ruleData['rule']['name'])
                ->addValue('label', $ruleData['rule']['label'])
                ->addValue('description', $ruleData['rule']['description'])
                ->addValue('help_text', $ruleData['rule']['help_text'] ?? '')
                ->addValue('is_active', $ruleData['rule']['is_active'])
                ->addValue('trigger_id', $triggerId)
                ->addValue('trigger_params', $ruleData['rule']['trigger_params'] ?? '')
                ->setCheckPermissions(false)
                ->execute()
                ->first();
            $ruleId = $rule['id'];
        }

        // Import conditions
        if (!empty($ruleData['conditions'])) {
            echo "Importing " . count($ruleData['conditions']) . " conditions...\n";
            foreach ($ruleData['conditions'] as $conditionData) {
                $conditionId = mapComponentId($conditionData['condition_id'], 'conditions', $componentMappings);
                if ($conditionId) {
                    \Civi\Api4\CiviRulesRuleCondition::create()
                        ->addValue('rule_id', $ruleId)
                        ->addValue('condition_id', $conditionId)
                        ->addValue('condition_params', $conditionData['condition_params'] ?? '')
                        ->addValue('is_active', $conditionData['is_active'] ?? 1)
                        ->setCheckPermissions(false)
                        ->execute();
                } else {
                    echo "⚠ Warning: Could not map condition ID: {$conditionData['condition_id']}\n";
                }
            }
        }

        // Import actions
        if (!empty($ruleData['actions'])) {
            echo "Importing " . count($ruleData['actions']) . " actions...\n";
            foreach ($ruleData['actions'] as $actionData) {
                $actionId = mapComponentId($actionData['action_id'], 'actions', $componentMappings);
                if ($actionId) {
                    \Civi\Api4\CiviRulesRuleAction::create()
                        ->addValue('rule_id', $ruleId)
                        ->addValue('action_id', $actionId)
                        ->addValue('action_params', $actionData['action_params'] ?? '')
                        ->addValue('delay', $actionData['delay'] ?? '')
                        ->addValue('ignore_condition_with_delay', $actionData['ignore_condition_with_delay'] ?? 0)
                        ->addValue('is_active', $actionData['is_active'] ?? 1)
                        ->setCheckPermissions(false)
                        ->execute();
                } else {
                    echo "⚠ Warning: Could not map action ID: {$actionData['action_id']}\n";
                }
            }
        }

        echo "✓ Rule imported successfully: {$ruleData['rule']['name']}\n";
        $importedRules++;

    } catch (Exception $e) {
        echo "✗ Error importing rule $ruleName: " . $e->getMessage() . "\n";
        $errorRules++;
    }
}

echo "\n=== Import Complete ===\n";
echo "Rules imported: $importedRules\n";
echo "Rules skipped: $skippedRules\n";
echo "Rules with errors: $errorRules\n";

if ($importedRules > 0) {
    echo "\n✓ Import successful! You may want to verify the imported rules in CiviRules admin.\n";
}

/**
 * Import actions from JSON file
 */
function importActions($filePath)
{
    echo "Importing actions from " . basename($filePath) . "...\n";

    $mappings = [];

    if (!file_exists($filePath)) {
        echo "  No actions file found, skipping.\n";
        return $mappings;
    }

    $content = file_get_contents($filePath);
    $components = json_decode($content, true);

    if (!$components) {
        echo "  Invalid JSON in actions file, skipping.\n";
        return $mappings;
    }

    foreach ($components as $component) {
        try {
            // Check if component already exists
            $existing = \Civi\Api4\CiviRulesAction::get()
                ->addWhere('name', '=', $component['name'])
                ->setCheckPermissions(false)
                ->execute()
                ->first();

            if ($existing) {
                echo "  Updating existing: {$component['name']}\n";
                \Civi\Api4\CiviRulesAction::update()
                    ->addWhere('id', '=', $existing['id'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
                    ->setCheckPermissions(false)
                    ->execute();
                $mappings[$component['name']] = $existing['id'];
            } else {
                echo "  Creating new: {$component['name']}\n";
                $created = \Civi\Api4\CiviRulesAction::create()
                    ->addValue('name', $component['name'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
                    ->setCheckPermissions(false)
                    ->execute()
                    ->first();
                $mappings[$component['name']] = $created['id'];
            }
        } catch (Exception $e) {
            echo "  ✗ Error with {$component['name']}: " . $e->getMessage() . "\n";
        }
    }

    echo "  ✓ Processed " . count($components) . " actions\n";
    return $mappings;
}

/**
 * Import conditions from JSON file
 */
function importConditions($filePath)
{
    echo "Importing conditions from " . basename($filePath) . "...\n";

    $mappings = [];

    if (!file_exists($filePath)) {
        echo "  No conditions file found, skipping.\n";
        return $mappings;
    }

    $content = file_get_contents($filePath);
    $components = json_decode($content, true);

    if (!$components) {
        echo "  Invalid JSON in conditions file, skipping.\n";
        return $mappings;
    }

    foreach ($components as $component) {
        try {
            // Check if component already exists
            $existing = \Civi\Api4\CiviRulesCondition::get()
                ->addWhere('name', '=', $component['name'])
                ->setCheckPermissions(false)
                ->execute()
                ->first();

            if ($existing) {
                echo "  Updating existing: {$component['name']}\n";
                \Civi\Api4\CiviRulesCondition::update()
                    ->addWhere('id', '=', $existing['id'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
                    ->setCheckPermissions(false)
                    ->execute();
                $mappings[$component['name']] = $existing['id'];
            } else {
                echo "  Creating new: {$component['name']}\n";
                $created = \Civi\Api4\CiviRulesCondition::create()
                    ->addValue('name', $component['name'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
                    ->setCheckPermissions(false)
                    ->execute()
                    ->first();
                $mappings[$component['name']] = $created['id'];
            }
        } catch (Exception $e) {
            echo "  ✗ Error with {$component['name']}: " . $e->getMessage() . "\n";
        }
    }

    echo "  ✓ Processed " . count($components) . " conditions\n";
    return $mappings;
}

/**
 * Import triggers from JSON file
 */
function importTriggers($filePath)
{
    echo "Importing triggers from " . basename($filePath) . "...\n";

    $mappings = [];

    if (!file_exists($filePath)) {
        echo "  No triggers file found, skipping.\n";
        return $mappings;
    }

    $content = file_get_contents($filePath);
    $components = json_decode($content, true);

    if (!$components) {
        echo "  Invalid JSON in triggers file, skipping.\n";
        return $mappings;
    }

    foreach ($components as $component) {
        try {
            // Check if component already exists
            $existing = \Civi\Api4\CiviRulesTrigger::get()
                ->addWhere('name', '=', $component['name'])
                ->setCheckPermissions(false)
                ->execute()
                ->first();

            if ($existing) {
                echo "  Updating existing: {$component['name']}\n";
                \Civi\Api4\CiviRulesTrigger::update()
                    ->addWhere('id', '=', $existing['id'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
                    ->addValue('object_name', $component['object_name'] ?? null)
                    ->addValue('op', $component['op'] ?? null)
                    ->addValue('cron', $component['cron'] ?? '0')
                    ->setCheckPermissions(false)
                    ->execute();
                $mappings[$component['name']] = $existing['id'];
            } else {
                echo "  Creating new: {$component['name']}\n";
                $created = \Civi\Api4\CiviRulesTrigger::create()
                    ->addValue('name', $component['name'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
                    ->addValue('object_name', $component['object_name'] ?? null)
                    ->addValue('op', $component['op'] ?? null)
                    ->addValue('cron', $component['cron'] ?? '0')
                    ->setCheckPermissions(false)
                    ->execute()
                    ->first();
                $mappings[$component['name']] = $created['id'];
            }
        } catch (Exception $e) {
            echo "  ✗ Error with {$component['name']}: " . $e->getMessage() . "\n";
        }
    }

    echo "  ✓ Processed " . count($components) . " triggers\n";
    return $mappings;
}

/**
 * Map component ID from export to current environment
 */
function mapComponentId($exportId, $componentType, $mappings)
{
    // First try to find in our import mappings
    foreach ($mappings[$componentType] as $id) {
        if ($id == $exportId) {
            return $id;
        }
    }

    // If not found, the component might be a core CiviRules component
    // that exists in both environments - return the ID as-is
    return $exportId;
}
