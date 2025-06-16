<?php

/**
 * CiviRules Export Tool - Unified Version
 *
 * Exports CiviRules rules and their custom actions, conditions, and triggers from the database
 * and converts them for target environment. Supports exporting to any environment (dev/prod)
 * with automatic ID mapping and URL conversion.
 *
 * USAGE:
 *   cv scr scripts/export_civirules.php --user=brian.flett@masadvise.org
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
 * $TARGET_ENVIRONMENT:
 *   - 'dev':  Export with dev-appropriate IDs and URLs
 *   - 'prod': Export with prod-appropriate IDs and URLs
 *   - 'current': Export with current environment IDs (no conversion)
 *
 * $LIST_ONLY:
 *   - true:  Only lists available rules and exits (no export)
 *   - false: Normal export behavior
 *
 * $DRY_RUN:
 *   - true:  Show what would be exported without creating files
 *   - false: Actually export the rules
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. List all available MAS rules for reference
 * 2. Export the specified rule(s) from the CiviRules database
 * 3. Apply environment-specific conversions (IDs)
 * 4. Save to the CiviRules directory with conversion metadata
 * 5. Update component JSON files (actions.json, conditions.json, triggers.json)
 * 6. Create export logs for tracking changes
 *
 * @author MAS Team
 * @version 2.0 (Unified Export with Environment Conversion)
 * @requires CiviCRM 6.1+, CiviRules extension
 */

echo "=== CiviRules Export Tool (Unified) ===\n\n";

// CONFIGURATION
$RULE_TO_EXPORT = '';                      // Change this to export different rules
$EXPORT_ALL = true;                        // Set to true to export all MAS rules
$TARGET_ENVIRONMENT = 'current';               // 'dev', 'prod', or 'current'
$LIST_ONLY = false;                       // Set to true to just list available rules
$DRY_RUN = false;                         // Set to true to preview export

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

// Get available MAS rules
try {
    $rules = \Civi\Api4\CiviRulesRule::get()
        ->addSelect('id', 'name', 'label', 'description', 'is_active')
        ->execute();
} catch (Exception $e) {
    echo "Error fetching rules: " . $e->getMessage() . "\n";
    echo "Make sure the CiviRules extension is installed and enabled.\n";
    exit(1);
}

if (empty($rules)) {
    echo "No CiviRules rules found!\n";
    exit(1);
}

echo "Available CiviRules rules:\n";
foreach ($rules as $rule) {
    $status = $rule['is_active'] ? 'Active' : 'Inactive';
    echo "  - {$rule['name']} ({$rule['label']}) [$status]\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($rules) . " rules available.\n";
    echo "To export, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine rules to export
$rulesToExport = [];
if ($EXPORT_ALL) {
    echo "\nExporting ALL rules...\n";
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

if (!$DRY_RUN && !is_dir($rulesDir)) {
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
    echo "\n--- " . ($DRY_RUN ? 'Preview' : 'Exporting') . " Rule: {$rule['name']} ---\n";

    try {
        // Get complete rule data
        $ruleData = \Civi\Api4\CiviRulesRule::get()
            ->addWhere('id', '=', $rule['id'])
            ->execute()
            ->first();

        // Get rule actions
        $ruleActions = \Civi\Api4\CiviRulesRuleAction::get()
            ->addWhere('rule_id', '=', $rule['id'])
            ->execute();

        // Get rule conditions
        $ruleConditions = \Civi\Api4\CiviRulesRuleCondition::get()
            ->addWhere('rule_id', '=', $rule['id'])
            ->execute();

        // Get rule trigger
        $ruleTrigger = null;
        if (!empty($ruleData['trigger_id'])) {
            $ruleTrigger = \Civi\Api4\CiviRulesTrigger::get()
                ->addWhere('id', '=', $ruleData['trigger_id'])
                ->execute()
                ->first();
        }

        // Analyze components for custom MAS components
        foreach ($ruleActions as $action) {
            if (!empty($action['action_id'])) {
                $actionDetails = \Civi\Api4\CiviRulesAction::get()
                    ->addWhere('id', '=', $action['action_id'])
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
            'exported_by' => 'MAS Export Script v2.0',
        ];

        // Apply environment conversion if needed
        if ($TARGET_ENVIRONMENT !== 'current' && $TARGET_ENVIRONMENT !== $currentEnv) {
            echo "Converting for $TARGET_ENVIRONMENT environment...\n";
            $ruleExport = convertCiviRulesForEnvironment($ruleExport, $currentEnv, $TARGET_ENVIRONMENT);
        }

        if ($DRY_RUN) {
            echo "✓ Rule data loaded and converted\n";
            echo "✓ Would save to: {$rulesDir}/{$rule['name']}.get.json\n";
        } else {
            // Export rule to JSON file
            $ruleFile = $rulesDir . '/' . $rule['name'] . '.get.json';
            file_put_contents($ruleFile, json_encode($ruleExport, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Rule exported: " . basename($ruleFile) . "\n";

            // Create ID mappings for this rule
            $mappings = createCiviRulesIdMappings($ruleExport, $currentEnv, $TARGET_ENVIRONMENT);
            $mappingsFile = $rulesDir . '/' . $rule['name'] . '.mappings.json';
            file_put_contents($mappingsFile, json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ ID mappings: " . basename($mappingsFile) . "\n";

            // Create export metadata
            $metadataFile = $rulesDir . '/' . $rule['name'] . '.export.log';
            $metadata = [
                'exported_date' => date('Y-m-d H:i:s'),
                'source_environment' => $currentEnv,
                'target_environment' => $TARGET_ENVIRONMENT,
                'rule_id' => $rule['id'],
                'rule_name' => $rule['name'],
                'rule_label' => $rule['label'],
                'export_version' => '2.0'
            ];
            file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Export metadata: " . basename($metadataFile) . "\n";
        }

    } catch (Exception $e) {
        echo "✗ Error exporting rule {$rule['name']}: " . $e->getMessage() . "\n";
    }
}

// Update component JSON files
if (!$DRY_RUN && (!empty($customActions) || !empty($customConditions) || !empty($customTriggers))) {
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
} elseif (!$DRY_RUN) {
    echo "\nNo custom components found to update.\n";
}

echo "\n=== Export Complete ===\n";
if (!$DRY_RUN) {
    echo "Files saved to: $baseDir\n";
}

/**
 * Convert CiviRules data for target environment
 */
function convertCiviRulesForEnvironment($ruleExport, $sourceEnv, $targetEnv)
{
    $converted = $ruleExport;

    // Get environment-specific mappings
    $mappings = getCiviRulesEnvironmentMappings($sourceEnv, $targetEnv);

    // Convert IDs in action parameters
    foreach ($converted['actions'] as &$ruleAction) {
        if (!empty($ruleAction['action_params'])) {
            $params = is_string($ruleAction['action_params']) ?
                json_decode($ruleAction['action_params'], true) :
                $ruleAction['action_params'];
            if (is_array($params)) {
                $newParams = convertCiviRulesIds($params, $mappings);
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
                $newParams = convertCiviRulesIds($params, $mappings);
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
            $newParams = convertCiviRulesIds($params, $mappings);
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
 * Convert individual IDs in parameters
 */
function convertCiviRulesIds($params, $mappings)
{
    foreach ($params as $key => &$value) {
        if (is_array($value)) {
            $value = convertCiviRulesIds($value, $mappings);
            continue;
        }

        if (!is_numeric($value)) {
            continue;
        }

        // Map based on parameter patterns
        if (preg_match('/contact_type|contact_sub_type/', $key) && isset($mappings['contact_types'][$value])) {
            $newValue = $mappings['contact_types'][$value];
            echo "    → Mapped contact type: $value → $newValue\n";
            $value = $newValue;
        } elseif (preg_match('/case_type/', $key) && isset($mappings['case_types'][$value])) {
            $newValue = $mappings['case_types'][$value];
            echo "    → Mapped case type: $value → $newValue\n";
            $value = $newValue;
        } elseif (preg_match('/activity_type/', $key) && isset($mappings['activity_types'][$value])) {
            $newValue = $mappings['activity_types'][$value];
            echo "    → Mapped activity type: $value → $newValue\n";
            $value = $newValue;
        } elseif (preg_match('/relationship_type/', $key) && isset($mappings['relationship_types'][$value])) {
            $newValue = $mappings['relationship_types'][$value];
            echo "    → Mapped relationship type: $value → $newValue\n";
            $value = $newValue;
        }
    }

    return $params;
}

/**
 * Get environment-specific ID mappings for CiviRules
 */
function getCiviRulesEnvironmentMappings($sourceEnv, $targetEnv)
{
    // CiviRules typically don't need as much ID conversion as forms
    // Most CiviRules IDs are system-level and consistent between environments
    // But we maintain the structure for future use

    if ($sourceEnv === 'dev' && $targetEnv === 'prod') {
        // Converting dev → prod
        return [
            'contact_types' => [],
            'case_types' => [],
            'activity_types' => [],
            'relationship_types' => [],
            'custom_fields' => [],
            'option_groups' => []
        ];
    } elseif ($sourceEnv === 'prod' && $targetEnv === 'dev') {
        // Converting prod → dev
        return [
            'contact_types' => [],
            'case_types' => [],
            'activity_types' => [],
            'relationship_types' => [],
            'custom_fields' => [],
            'option_groups' => []
        ];
    } else {
        // Same environment - no conversion needed
        return [
            'contact_types' => [],
            'case_types' => [],
            'activity_types' => [],
            'relationship_types' => [],
            'custom_fields' => [],
            'option_groups' => []
        ];
    }
}

/**
 * Create ID to name mappings for CiviRules data
 */
/**
 * Extract and map IDs found in serialized parameters
 */
function extractIdsFromParams($params, $mappings) {
    foreach ($params as $key => $value) {
        if (is_array($value)) {
            // Recursively check array values
            $mappings = extractIdsFromParams($value, $mappings);
        } elseif (is_string($key) && is_numeric($value)) {
            // Check for relationship types
            if (preg_match('/relationship_type/', $key)) {
                try {
                    $relType = \Civi\Api4\RelationshipType::get()
                        ->addWhere('id', '=', $value)
                        ->addSelect('id', 'name_a_b', 'label_a_b')
                        ->execute()
                        ->first();
                    if ($relType) {
                        $mappings['relationship_types'][$value] = $relType['name_a_b'];
                    }
                } catch (Exception $e) {
                    // Ignore missing relationship types
                }
            }
            // Check for case types
            elseif (preg_match('/case_type/', $key)) {
                try {
                    $caseType = \Civi\Api4\CaseType::get()
                        ->addWhere('id', '=', $value)
                        ->addSelect('id', 'name')
                        ->execute()
                        ->first();
                    if ($caseType) {
                        $mappings['case_types'][$value] = $caseType['name'];
                    }
                } catch (Exception $e) {
                    // Ignore missing case types
                }
            }
            // Check for activity types
            elseif (preg_match('/activity_type/', $key)) {
                try {
                    $activityType = \Civi\Api4\OptionValue::get()
                        ->addWhere('option_group_id:name', '=', 'activity_type')
                        ->addWhere('value', '=', $value)
                        ->addSelect('value', 'name')
                        ->execute()
                        ->first();
                    if ($activityType) {
                        $mappings['activity_types'][$value] = $activityType['name'];
                    }
                } catch (Exception $e) {
                    // Ignore missing activity types
                }
            }
        }
    }
    return $mappings;
}

function createCiviRulesIdMappings($ruleExport, $sourceEnv, $targetEnv)
{
    $mappings = [
        'source_environment' => $sourceEnv,
        'target_environment' => $targetEnv,
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
            $trigger = \Civi\Api4\CiviRulesTrigger::get()
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
                $action = \Civi\Api4\CiviRulesAction::get()
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
            $params = @unserialize($ruleAction['action_params']);
            if ($params && is_array($params)) {
                $mappings = extractIdsFromParams($params, $mappings);
            }
        }
    }

    // Map conditions
    foreach ($ruleExport['conditions'] as $ruleCondition) {
        if (!empty($ruleCondition['condition_id'])) {
            try {
                $condition = \Civi\Api4\CiviRulesCondition::get()
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
            $params = @unserialize($ruleCondition['condition_params']);
            if ($params && is_array($params)) {
                $mappings = extractIdsFromParams($params, $mappings);
            }
        }
    }

    return $mappings;
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
