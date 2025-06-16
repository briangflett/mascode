<?php

/**
 * CiviRules Import Tool - Unified Version
 *
 * Imports CiviRules rules and their custom actions, conditions, and triggers from JSON files
 * and converts them for current environment. Supports importing from any environment (dev/prod)
 * with automatic ID mapping and conversion.
 *
 * USAGE:
 *   cv scr scripts/import_civirules_unified.php --user=brian.flett@masadvise.org
 *
 * CONFIGURATION (edit the variables below):
 *
 * $RULE_TO_IMPORT:
 *   - Set to the name of a specific rule to import (e.g., 'mas_create_project_from_sr')
 *   - Only used when $IMPORT_ALL is false
 *   - Rule names are case-sensitive and must match the JSON filename (without .get.json)
 *
 * $IMPORT_ALL:
 *   - true:  Imports ALL rule .get.json files found in the rules directory
 *   - false: Imports only the rule specified in $RULE_TO_IMPORT
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
 *   - true:  Updates existing rules if they already exist
 *   - false: Skips rules that already exist (safer option)
 *
 * $DRY_RUN:
 *   - true:  Show what would be imported without making changes
 *   - false: Actually import the rules
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. Import custom components (actions, conditions, triggers) from JSON files
 * 2. List all available rule JSON files for reference
 * 3. Load rule data and detect source environment
 * 4. Apply environment-specific conversions for current environment
 * 5. Import rules using the CiviRules API
 * 6. Handle existing rules based on $UPDATE_EXISTING setting
 * 7. Create import logs for tracking changes
 *
 * @author MAS Team
 * @version 2.0 (Unified Import with Environment Conversion)
 * @requires CiviCRM 6.1+, CiviRules extension
 */

echo "=== CiviRules Import Tool (Unified) ===\n\n";

// CONFIGURATION
$RULE_TO_IMPORT = '';                       // Change this to import specific rule
$IMPORT_ALL = true;                         // Set to true to import all available rules
$SOURCE_ENVIRONMENT = 'auto';               // 'auto', 'dev', 'prod', or 'current'
$LIST_ONLY = false;                        // Set to true to just list available files
$UPDATE_EXISTING = true;                   // Set to true to update existing rules
$DRY_RUN = false;                          // Set to true to preview import

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

// Define paths
$baseDir = \CRM_Mascode_ExtensionUtil::path('Civi/Mascode/CiviRules');
$rulesDir = $baseDir . '/rules';

// Check if import directory exists
if (!is_dir($rulesDir)) {
    echo "Error: Rules directory not found: $rulesDir\n";
    echo "Make sure you've exported rules first using export_civirules.php\n";
    exit(1);
}

// Get available rule files (.get.json files)
$ruleFiles = glob($rulesDir . '/*.get.json');
if (empty($ruleFiles)) {
    echo "No rule .get.json files found in: $rulesDir\n";
    echo "Make sure you've exported rules first using export_civirules.php\n";
    exit(1);
}

echo "Available rule files:\n";
$availableFiles = [];
foreach ($ruleFiles as $file) {
    $ruleName = basename($file, '.get.json');
    $mappingsFile = $rulesDir . '/' . $ruleName . '.mappings.json';
    $metadataFile = $rulesDir . '/' . $ruleName . '.export.log';
    
    $availableFiles[$ruleName] = [
        'get_file' => $file,
        'mappings_file' => file_exists($mappingsFile) ? $mappingsFile : null,
        'metadata_file' => file_exists($metadataFile) ? $metadataFile : null
    ];
    
    $metadataStatus = $availableFiles[$ruleName]['metadata_file'] ? '✓ With metadata' : '⚠ No metadata';
    $mappingsStatus = $availableFiles[$ruleName]['mappings_file'] ? '✓ With mappings' : '⚠ No mappings';
    echo "  - $ruleName ($metadataStatus, $mappingsStatus)\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($availableFiles) . " rule files available.\n";
    echo "To import, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine files to import
$filesToImport = [];
if ($IMPORT_ALL) {
    echo "\nImporting ALL available rules...\n";
    $filesToImport = array_keys($availableFiles);
} else {
    echo "\nImporting rule: {$RULE_TO_IMPORT}\n";
    if (!isset($availableFiles[$RULE_TO_IMPORT])) {
        echo "Error: Rule file not found: $RULE_TO_IMPORT\n";
        echo "Available files are listed above.\n";
        exit(1);
    }
    $filesToImport[] = $RULE_TO_IMPORT;
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
    $componentMappings['actions'] = importCiviRulesActions($actionsFile, $DRY_RUN);
}

// Import conditions
$conditionsFile = $baseDir . '/conditions.json';
if (file_exists($conditionsFile)) {
    $componentMappings['conditions'] = importCiviRulesConditions($conditionsFile, $DRY_RUN);
}

// Import triggers
$triggersFile = $baseDir . '/triggers.json';
if (file_exists($triggersFile)) {
    $componentMappings['triggers'] = importCiviRulesTriggers($triggersFile, $DRY_RUN);
}

// Import rules
echo "\n--- Importing Rules ---\n";
$importedRules = 0;
$skippedRules = 0;
$errorRules = 0;

foreach ($filesToImport as $ruleName) {
    echo "\n--- " . ($DRY_RUN ? 'Preview' : 'Importing') . " Rule: $ruleName ---\n";

    try {
        $files = $availableFiles[$ruleName];

        // Load rule data
        $content = file_get_contents($files['get_file']);
        $ruleData = json_decode($content, true);

        if (!$ruleData) {
            echo "✗ Error: Invalid JSON in file: " . basename($files['get_file']) . "\n";
            $errorRules++;
            continue;
        }

        // Load metadata if available
        $metadata = [];
        if ($files['metadata_file']) {
            $metadataContent = file_get_contents($files['metadata_file']);
            $metadata = json_decode($metadataContent, true);
            if (!$metadata) {
                echo "⚠ Warning: Could not read metadata from: " . basename($files['metadata_file']) . "\n";
                $metadata = [];
            }
        }

        // Load ID mappings
        $idMappings = [];
        if ($files['mappings_file']) {
            $mappingsContent = file_get_contents($files['mappings_file']);
            $idMappings = json_decode($mappingsContent, true);
            if (!$idMappings) {
                echo "⚠ Warning: Could not read mappings from: " . basename($files['mappings_file']) . "\n";
                $idMappings = [];
            }
        }

        // Determine source environment
        $sourceEnv = $SOURCE_ENVIRONMENT;
        if ($sourceEnv === 'auto') {
            $sourceEnv = detectCiviRulesSourceEnvironment($ruleData, $metadata);
            echo "Auto-detected source environment: $sourceEnv\n";
        }

        // Apply environment conversion if needed
        $convertedRuleData = $ruleData;
        if ($sourceEnv !== 'current' && $sourceEnv !== $currentEnv) {
            echo "Converting from $sourceEnv to $currentEnv environment...\n";
            $convertedRuleData = convertCiviRulesFromEnvironment($ruleData, $idMappings, $sourceEnv, $currentEnv);
        }

        // Check if rule already exists
        $existingRule = \Civi\Api4\CiviRulesRule::get()
            ->addWhere('name', '=', $convertedRuleData['rule']['name'])
            ->execute()
            ->first();

        if ($existingRule && !$UPDATE_EXISTING) {
            echo "⚠ Skipping existing rule: {$convertedRuleData['rule']['name']}\n";
            $skippedRules++;
            continue;
        }

        if ($DRY_RUN) {
            echo "✓ Rule data loaded and converted\n";
            if ($existingRule) {
                echo "✓ Would update existing rule: $ruleName\n";
            } else {
                echo "✓ Would create new rule: $ruleName\n";
            }
        } else {
            // Actually import the rule
            $result = importCiviRule($convertedRuleData, $existingRule, $componentMappings);
            
            if ($result) {
                echo "✓ Rule imported successfully: $ruleName\n";
                $importedRules++;
                
                // Create import log
                $logFile = $rulesDir . '/' . $ruleName . '.import.log';
                $logData = [
                    'imported_date' => date('Y-m-d H:i:s'),
                    'source_file' => $files['get_file'],
                    'source_environment' => $sourceEnv,
                    'target_environment' => $currentEnv,
                    'rule_name' => $ruleName,
                    'was_update' => !empty($existingRule),
                    'import_version' => '2.0'
                ];
                file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo "✓ Import log: " . basename($logFile) . "\n";
            } else {
                echo "✗ Import failed for: $ruleName\n";
                $errorRules++;
            }
        }

    } catch (Exception $e) {
        echo "✗ Error " . ($DRY_RUN ? 'previewing' : 'importing') . " $ruleName: " . $e->getMessage() . "\n";
        $errorRules++;
    }
}

echo "\n=== " . ($DRY_RUN ? 'Preview' : 'Import') . " Complete ===\n";
echo "Rules imported: $importedRules\n";
echo "Rules skipped: $skippedRules\n";
echo "Rules with errors: $errorRules\n";

if ($importedRules > 0 && !$DRY_RUN) {
    echo "\n✓ Import successful! You may want to verify the imported rules in CiviRules admin.\n";
}

/**
 * Import CiviRules actions from JSON file
 */
function importCiviRulesActions($filePath, $dryRun = false)
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
        if ($dryRun) {
            echo "  Would process action: {$component['name']}\n";
            continue;
        }

        try {
            // Check if component already exists
            $existing = \Civi\Api4\CiviRulesAction::get()
                ->addWhere('name', '=', $component['name'])
                ->execute()
                ->first();

            if ($existing) {
                echo "  Updating existing: {$component['name']}\n";
                \Civi\Api4\CiviRulesAction::update()
                    ->addWhere('id', '=', $existing['id'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
                    ->execute();
                $mappings[$component['name']] = $existing['id'];
            } else {
                echo "  Creating new: {$component['name']}\n";
                $created = \Civi\Api4\CiviRulesAction::create()
                    ->addValue('name', $component['name'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
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
 * Import CiviRules conditions from JSON file
 */
function importCiviRulesConditions($filePath, $dryRun = false)
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
        if ($dryRun) {
            echo "  Would process condition: {$component['name']}\n";
            continue;
        }

        try {
            // Check if component already exists
            $existing = \Civi\Api4\CiviRulesCondition::get()
                ->addWhere('name', '=', $component['name'])
                ->execute()
                ->first();

            if ($existing) {
                echo "  Updating existing: {$component['name']}\n";
                \Civi\Api4\CiviRulesCondition::update()
                    ->addWhere('id', '=', $existing['id'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
                    ->execute();
                $mappings[$component['name']] = $existing['id'];
            } else {
                echo "  Creating new: {$component['name']}\n";
                $created = \Civi\Api4\CiviRulesCondition::create()
                    ->addValue('name', $component['name'])
                    ->addValue('label', $component['label'])
                    ->addValue('class_name', $component['class_name'])
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
 * Import CiviRules triggers from JSON file
 */
function importCiviRulesTriggers($filePath, $dryRun = false)
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
        if ($dryRun) {
            echo "  Would process trigger: {$component['name']}\n";
            continue;
        }

        try {
            // Check if component already exists
            $existing = \Civi\Api4\CiviRulesTrigger::get()
                ->addWhere('name', '=', $component['name'])
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
 * Import a single CiviRule
 */
function importCiviRule($ruleData, $existingRule, $componentMappings)
{
    try {
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
                    ->execute()
                    ->first();
                if ($trigger) {
                    $triggerId = $trigger['id'];
                }
            }

            if (!$triggerId) {
                echo "✗ Error: Required trigger not found: $triggerName\n";
                return false;
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
                ->execute()
                ->first();
            $ruleId = $existingRule['id'];

            // Delete existing conditions and actions
            \Civi\Api4\CiviRulesRuleCondition::delete()
                ->addWhere('rule_id', '=', $ruleId)
                ->execute();

            \Civi\Api4\CiviRulesRuleAction::delete()
                ->addWhere('rule_id', '=', $ruleId)
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
                ->execute()
                ->first();
            $ruleId = $rule['id'];
        }

        // Import conditions
        if (!empty($ruleData['conditions'])) {
            echo "Importing " . count($ruleData['conditions']) . " conditions...\n";
            foreach ($ruleData['conditions'] as $conditionData) {
                $conditionId = mapCiviRulesComponentId($conditionData['condition_id'], 'conditions', $componentMappings);
                if ($conditionId) {
                    \Civi\Api4\CiviRulesRuleCondition::create()
                        ->addValue('rule_id', $ruleId)
                        ->addValue('condition_id', $conditionId)
                        ->addValue('condition_params', $conditionData['condition_params'] ?? '')
                        ->addValue('is_active', $conditionData['is_active'] ?? 1)
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
                $actionId = mapCiviRulesComponentId($actionData['action_id'], 'actions', $componentMappings);
                if ($actionId) {
                    \Civi\Api4\CiviRulesRuleAction::create()
                        ->addValue('rule_id', $ruleId)
                        ->addValue('action_id', $actionId)
                        ->addValue('action_params', $actionData['action_params'] ?? '')
                        ->addValue('delay', $actionData['delay'] ?? '')
                        ->addValue('ignore_condition_with_delay', $actionData['ignore_condition_with_delay'] ?? 0)
                        ->addValue('is_active', $actionData['is_active'] ?? 1)
                        ->execute();
                } else {
                    echo "⚠ Warning: Could not map action ID: {$actionData['action_id']}\n";
                }
            }
        }

        return true;

    } catch (Exception $e) {
        echo "Import error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Detect source environment from rule data and metadata
 */
function detectCiviRulesSourceEnvironment($ruleData, $metadata)
{
    // Check metadata first
    if (!empty($metadata['target_environment'])) {
        return $metadata['target_environment'];
    }
    
    // For CiviRules, environment detection is less clear since most IDs are consistent
    // Default to dev for safety
    return 'dev';
}

/**
 * Convert CiviRules data from source environment to target environment
 */
function convertCiviRulesFromEnvironment($ruleData, $idMappings, $sourceEnv, $targetEnv)
{
    $converted = $ruleData;
    
    // Get conversion mappings
    $conversionMappings = getCiviRulesEnvironmentConversionMappings($sourceEnv, $targetEnv);
    
    // Convert IDs in action parameters
    foreach ($converted['actions'] as &$ruleAction) {
        if (!empty($ruleAction['action_params'])) {
            $params = is_string($ruleAction['action_params']) ?
                json_decode($ruleAction['action_params'], true) :
                $ruleAction['action_params'];
            if (is_array($params)) {
                $newParams = convertCiviRulesParameterIds($params, $idMappings, $conversionMappings);
                if ($newParams !== $params) {
                    $ruleAction['action_params'] = is_string($ruleAction['action_params']) ?
                        json_encode($newParams) : $newParams;
                    echo "  → Converted action parameters\n";
                }
            }
        }
    }

    // Convert IDs in condition parameters
    foreach ($converted['conditions'] as &$ruleCondition) {
        if (!empty($ruleCondition['condition_params'])) {
            $params = is_string($ruleCondition['condition_params']) ?
                json_decode($ruleCondition['condition_params'], true) :
                $ruleCondition['condition_params'];
            if (is_array($params)) {
                $newParams = convertCiviRulesParameterIds($params, $idMappings, $conversionMappings);
                if ($newParams !== $params) {
                    $ruleCondition['condition_params'] = is_string($ruleCondition['condition_params']) ?
                        json_encode($newParams) : $newParams;
                    echo "  → Converted condition parameters\n";
                }
            }
        }
    }

    // Convert IDs in trigger parameters
    if (!empty($converted['rule']['trigger_params'])) {
        $params = is_string($converted['rule']['trigger_params']) ?
            json_decode($converted['rule']['trigger_params'], true) :
            $converted['rule']['trigger_params'];
        if (is_array($params)) {
            $newParams = convertCiviRulesParameterIds($params, $idMappings, $conversionMappings);
            if ($newParams !== $params) {
                $converted['rule']['trigger_params'] = is_string($converted['rule']['trigger_params']) ?
                    json_encode($newParams) : $newParams;
                echo "  → Converted trigger parameters\n";
            }
        }
    }
    
    return $converted;
}

/**
 * Convert parameter IDs using mappings
 */
function convertCiviRulesParameterIds($params, $idMappings, $conversionMappings)
{
    foreach ($params as $key => &$value) {
        if (is_array($value)) {
            $value = convertCiviRulesParameterIds($value, $idMappings, $conversionMappings);
            continue;
        }

        if (!is_numeric($value)) {
            continue;
        }

        // Map based on parameter patterns and available mappings
        if (preg_match('/contact_type|contact_sub_type/', $key) && !empty($idMappings['contact_types'][$value])) {
            $name = $idMappings['contact_types'][$value];
            $prodId = lookupCiviRulesContactTypeId($name);
            if ($prodId) {
                echo "    → Mapped contact type '$name': $value → $prodId\n";
                $value = $prodId;
            }
        } elseif (preg_match('/case_type/', $key) && !empty($idMappings['case_types'][$value])) {
            $name = $idMappings['case_types'][$value];
            $prodId = lookupCiviRulesCaseTypeId($name);
            if ($prodId) {
                echo "    → Mapped case type '$name': $value → $prodId\n";
                $value = $prodId;
            }
        } elseif (preg_match('/activity_type/', $key) && !empty($idMappings['activity_types'][$value])) {
            $name = $idMappings['activity_types'][$value];
            $prodId = lookupCiviRulesActivityTypeId($name);
            if ($prodId) {
                echo "    → Mapped activity type '$name': $value → $prodId\n";
                $value = $prodId;
            }
        } elseif (preg_match('/relationship_type/', $key) && !empty($idMappings['relationship_types'][$value])) {
            $name = $idMappings['relationship_types'][$value];
            $prodId = lookupCiviRulesRelationshipTypeId($name);
            if ($prodId) {
                echo "    → Mapped relationship type '$name': $value → $prodId\n";
                $value = $prodId;
            }
        }
    }

    return $params;
}

/**
 * Get environment conversion mappings for CiviRules
 */
function getCiviRulesEnvironmentConversionMappings($sourceEnv, $targetEnv)
{
    // CiviRules typically don't need as much ID conversion as forms
    // Most CiviRules IDs are system-level and consistent between environments
    return [
        'contact_types' => [],
        'case_types' => [],
        'activity_types' => [],
        'relationship_types' => []
    ];
}

/**
 * Lookup functions for CiviRules
 */
function lookupCiviRulesContactTypeId($contactTypeName)
{
    try {
        $result = \Civi\Api4\ContactType::get()
            ->addWhere('name', '=', $contactTypeName)
            ->addSelect('id')
            ->execute()
            ->first();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        return null;
    }
}

function lookupCiviRulesCaseTypeId($caseTypeName)
{
    try {
        $result = \Civi\Api4\CaseType::get()
            ->addWhere('name', '=', $caseTypeName)
            ->addSelect('id')
            ->execute()
            ->first();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        return null;
    }
}

function lookupCiviRulesActivityTypeId($activityTypeName)
{
    try {
        $result = \Civi\Api4\OptionValue::get()
            ->addWhere('option_group_id:name', '=', 'activity_type')
            ->addWhere('name', '=', $activityTypeName)
            ->addSelect('value')
            ->execute()
            ->first();
        return $result ? $result['value'] : null;
    } catch (Exception $e) {
        return null;
    }
}

function lookupCiviRulesRelationshipTypeId($relationshipTypeName)
{
    try {
        $result = \Civi\Api4\RelationshipType::get()
            ->addWhere('name_a_b', '=', $relationshipTypeName)
            ->addSelect('id')
            ->execute()
            ->first();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Map component ID from export to current environment
 */
function mapCiviRulesComponentId($exportId, $componentType, $mappings)
{
    // First try to find in our import mappings
    foreach ($mappings[$componentType] as $name => $id) {
        if ($id == $exportId) {
            return $id;
        }
    }

    // If not found, the component might be a core CiviRules component
    // that exists in both environments - return the ID as-is
    return $exportId;
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