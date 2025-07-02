<?php

/**
 * MAS CiviRules Deployment Script
 * 
 * This script creates the complete CiviRules system for MAS including:
 * - Custom triggers, conditions, and actions
 * - All 6 MAS CiviRules with proper configuration
 * - Environment-specific ID mapping
 * 
 * IMPORTANT: Update the configuration section below for your target environment
 * NOTE: Comment out any rules in $civiRulesDefinitions that you want to skip deployment
 */

// ============================================================================
// ENVIRONMENT CONFIGURATION - UPDATE THESE VALUES FOR YOUR TARGET ENVIRONMENT
// ============================================================================

$config = [
    // Environment identifier
    'environment' => 'dev', // 'dev' or 'prod'
    
    // Activity type mappings (find these in your target environment)
    'activity_types' => [
        'Open Case' => 13,              // Default CiviCRM value, usually consistent
        'Follow up' => 14,              // Default CiviCRM value, usually consistent
        'Change Case Subject' => 53,    // Default CiviCRM value, usually consistent
    ],
    
    // Case type mappings (find these in your target environment)
    'case_types' => [
        'service_request' => 3,         // Update this ID for your environment
        'mas_project' => 4,             // Update this ID for your environment
    ],
    
    // Relationship type mappings (find these in your target environment)
    'relationship_types' => [
        'President of' => 11,           // Update this ID for your environment
        'Executive Director of' => 12,  // Update this ID for your environment
        'Employer of' => 4,             // Usually consistent across environments
    ],
    
    // Contact sub-type mappings
    'contact_sub_types' => [
        'MAS_Rep' => 'MAS_Rep',         // Usually consistent if using same names
    ],
    
    // Custom field mappings (these may vary between environments)
    'custom_fields' => [
        'Cases_SR_Projects_.MAS_Code' => 'Cases_SR_Projects_.MAS_Code',
        'Cases_SR_Projects_.Notes' => 'Cases_SR_Projects_.Notes',
        'Projects.MAS_Code' => 'Projects.MAS_Code',
        'Projects.Notes' => 'Projects.Notes',
    ],
    
    // Group mappings (if using groups in conditions)
    'groups' => [
        // Add any group mappings here if needed
        // 'group_name' => group_id,
    ],
];

// ============================================================================
// CIVIRULES DEFINITIONS - COMMENT OUT ANY RULES YOU WANT TO SKIP
// ============================================================================

$civiRulesDefinitions = [
    
    // Rule 1: Create a project from a service request
    [
        'name' => 'create_a_project_from_a_service_request',
        'label' => 'Create a project from a service request',
        'description' => 'When a case is created of type service_request, create a new case of type mas_project',
        'is_active' => 1,
        'trigger' => 'civi_case_add',
        'conditions' => [
            [
                'condition' => 'case_case_type',
                'parameters' => [
                    'case_type_id' => ['case_types', 'service_request'], // Reference to config
                ],
            ]
        ],
        'actions' => [
            [
                'action' => 'mas_create_project_from_sr',
                'parameters' => [
                    'case_type_id' => ['case_types', 'mas_project'], // Reference to config
                ],
            ]
        ],
    ],
    
    // Rule 2: Create president relationship when individual is added
    [
        'name' => 'create_a_president_relationship_when_individual_is_added',
        'label' => 'Create a President relationship when Individual is added',
        'description' => 'Create relationship between contact and employer when job title is President',
        'is_active' => 1,
        'trigger' => 'civi_contact_add',
        'conditions' => [
            [
                'condition' => 'contact_has_subtype',
                'parameters' => [
                    'contact_sub_type' => ['contact_sub_types', 'MAS_Rep'],
                ],
            ],
        ],
        'actions' => [
            [
                'action' => 'mas_add_relationship_to_employer',
                'parameters' => [
                    'relationship_type_id' => ['relationship_types', 'President of'],
                    'job_title_match' => 'President',
                ],
            ]
        ],
    ],
    
    // Rule 3: Create president relationship when individual is modified
    [
        'name' => 'create_a_president_relationship_when_individual_is_modified',
        'label' => 'Create a President relationship when Individual is modified',
        'description' => 'Create relationship between contact and employer when job title is modified to President',
        'is_active' => 1,
        'trigger' => 'civi_contact_edit',
        'conditions' => [
            [
                'condition' => 'contact_has_subtype',
                'parameters' => [
                    'contact_sub_type' => ['contact_sub_types', 'MAS_Rep'],
                ],
            ]
        ],
        'actions' => [
            [
                'action' => 'mas_add_relationship_to_employer',
                'parameters' => [
                    'relationship_type_id' => ['relationship_types', 'President of'],
                    'job_title_match' => 'President',
                ],
            ]
        ],
    ],
    
    // Rule 4: Create executive director relationship when individual is added
    [
        'name' => 'create_executive_director_relationship_when_individual_is_added',
        'label' => 'Create Executive Director relationship when Individual is added',
        'description' => 'Create relationship between contact and employer when job title is Executive Director',
        'is_active' => 1,
        'trigger' => 'civi_contact_add',
        'conditions' => [
            [
                'condition' => 'contact_has_subtype',
                'parameters' => [
                    'contact_sub_type' => ['contact_sub_types', 'MAS_Rep'],
                ],
            ]
        ],
        'actions' => [
            [
                'action' => 'mas_add_relationship_to_employer',
                'parameters' => [
                    'relationship_type_id' => ['relationship_types', 'Executive Director of'],
                    'job_title_match' => 'Executive Director',
                ],
            ]
        ],
    ],
    
    // Rule 5: Create executive director relationship when individual is changed
    [
        'name' => 'create_executive_director_relationship_when_individual_is_changed',
        'label' => 'Create Executive Director relationship when Individual is changed',
        'description' => 'Create relationship between contact and employer when job title is modified to Executive Director',
        'is_active' => 1,
        'trigger' => 'civi_contact_edit',
        'conditions' => [
            [
                'condition' => 'contact_has_subtype',
                'parameters' => [
                    'contact_sub_type' => ['contact_sub_types', 'MAS_Rep'],
                ],
            ]
        ],
        'actions' => [
            [
                'action' => 'mas_add_relationship_to_employer',
                'parameters' => [
                    'relationship_type_id' => ['relationship_types', 'Executive Director of'],
                    'job_title_match' => 'Executive Director',
                ],
            ]
        ],
    ],
    
    // Rule 6: Generate MAS case code
    [
        'name' => 'generate_a_mas_case_code',
        'label' => 'Generate a MAS case code',
        'description' => 'Generate a unique MAS code when a case is created',
        'is_active' => 1,
        'trigger' => 'civi_case_add',
        'conditions' => [
            [
                'condition' => 'case_case_type',
                'parameters' => [
                    'case_type_id' => ['case_types', 'service_request'],
                ],
            ]
        ],
        'actions' => [
            [
                'action' => 'mas_generate_mas_code',
                'parameters' => [
                    'custom_field' => ['custom_fields', 'Cases_SR_Projects_.MAS_Code'],
                ],
            ]
        ],
    ],
];

// ============================================================================
// DEPLOYMENT SCRIPT - DO NOT MODIFY BELOW THIS LINE
// ============================================================================

echo "=== MAS CiviRules Deployment Script ===\n";
echo "Environment: {$config['environment']}\n";
echo "Starting deployment...\n\n";

$results = [];

try {
    // Step 1: Validate environment
    echo "Step 1: Validating environment...\n";
    validateEnvironment($config);
    echo "✓ Environment validation passed\n\n";
    
    // Step 2: Get component mappings
    echo "Step 2: Mapping CiviRules components...\n";
    $componentMappings = getComponentMappings();
    echo "✓ Found " . count($componentMappings['triggers']) . " triggers, " . 
         count($componentMappings['conditions']) . " conditions, " . 
         count($componentMappings['actions']) . " actions\n\n";
    
    // Step 3: Deploy each CiviRule
    echo "Step 3: Deploying CiviRules...\n";
    
    $deployed = 0;
    $updated = 0;
    $skipped = 0;
    
    foreach ($civiRulesDefinitions as $ruleDefinition) {
        $result = deployCiviRule($ruleDefinition, $config, $componentMappings);
        
        if ($result['action'] === 'created') {
            $deployed++;
            echo "✓ Created rule: {$ruleDefinition['label']}\n";
        } elseif ($result['action'] === 'updated') {
            $updated++;
            echo "✓ Updated rule: {$ruleDefinition['label']}\n";
        } else {
            $skipped++;
            echo "- Skipped rule: {$ruleDefinition['label']} (already exists and unchanged)\n";
        }
        
        $results[$ruleDefinition['name']] = $result;
    }
    
    echo "\n=== DEPLOYMENT COMPLETED SUCCESSFULLY ===\n";
    echo "Summary:\n";
    echo "- Rules created: $deployed\n";
    echo "- Rules updated: $updated\n";
    echo "- Rules skipped: $skipped\n";
    echo "- Total rules: " . count($civiRulesDefinitions) . "\n";
    echo "\nDeployed rules:\n";
    
    foreach ($results as $ruleName => $result) {
        echo "- {$ruleName}: Rule ID {$result['rule_id']}\n";
    }
    
    echo "\nDon't forget to flush the CiviCRM cache!\n";
    
} catch (Exception $e) {
    echo "\n❌ DEPLOYMENT FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Validate that the environment configuration is correct
 */
function validateEnvironment($config) {
    // Check if CiviRules extension is installed
    $extension = \Civi\Api4\Extension::get(FALSE)
        ->addWhere('full_name', '=', 'civirules')
        ->addWhere('status', '=', 'installed')
        ->execute()->first();
    
    if (!$extension) {
        throw new Exception('CiviRules extension is not installed or enabled');
    }
    
    // Validate case types exist
    foreach ($config['case_types'] as $name => $id) {
        $caseType = \Civi\Api4\CaseType::get(FALSE)
            ->addWhere('id', '=', $id)
            ->execute()->first();
        
        if (!$caseType) {
            throw new Exception("Case type '$name' with ID $id not found");
        }
    }
    
    // Validate relationship types exist
    foreach ($config['relationship_types'] as $name => $id) {
        $relType = \Civi\Api4\RelationshipType::get(FALSE)
            ->addWhere('id', '=', $id)
            ->execute()->first();
        
        if (!$relType) {
            throw new Exception("Relationship type '$name' with ID $id not found");
        }
    }
    
    return true;
}

/**
 * Get mappings of all CiviRules components using API4
 */
function getComponentMappings() {
    // Get triggers
    $triggers = [];
    $triggerData = \Civi\Api4\CiviRulesTrigger::get(FALSE)
        ->execute();
    foreach ($triggerData as $trigger) {
        $triggers[$trigger['name']] = $trigger['id'];
    }
    
    // Get conditions
    $conditions = [];
    $conditionData = \Civi\Api4\CiviRulesCondition::get(FALSE)
        ->execute();
    foreach ($conditionData as $condition) {
        $conditions[$condition['name']] = $condition['id'];
    }
    
    // Get actions
    $actions = [];
    $actionData = \Civi\Api4\CiviRulesAction::get(FALSE)
        ->execute();
    foreach ($actionData as $action) {
        $actions[$action['name']] = $action['id'];
    }
    
    return [
        'triggers' => $triggers,
        'conditions' => $conditions,
        'actions' => $actions,
    ];
}

/**
 * Deploy a single CiviRule using API4
 */
function deployCiviRule($ruleDefinition, $config, $componentMappings) {
    // Check if rule already exists
    $existingRule = \Civi\Api4\CiviRulesRule::get(FALSE)
        ->addWhere('name', '=', $ruleDefinition['name'])
        ->execute()->first();
    
    if (!isset($componentMappings['triggers'][$ruleDefinition['trigger']])) {
        throw new Exception("Trigger '{$ruleDefinition['trigger']}' not found");
    }
    
    $ruleData = [
        'name' => $ruleDefinition['name'],
        'label' => $ruleDefinition['label'],
        'description' => $ruleDefinition['description'],
        'is_active' => $ruleDefinition['is_active'],
        'trigger_id' => $componentMappings['triggers'][$ruleDefinition['trigger']],
    ];
    
    if ($existingRule) {
        // Update existing rule
        $rule = \Civi\Api4\CiviRulesRule::update(FALSE)
            ->addWhere('id', '=', $existingRule['id'])
            ->setValues($ruleData)
            ->execute()->first();
        $action = 'updated';
        $ruleId = $existingRule['id'];
        
        // Clear existing conditions and actions
        \Civi\Api4\CiviRulesRuleCondition::delete(FALSE)
            ->addWhere('rule_id', '=', $ruleId)
            ->execute();
        \Civi\Api4\CiviRulesRuleAction::delete(FALSE)
            ->addWhere('rule_id', '=', $ruleId)
            ->execute();
    } else {
        // Create new rule
        $rule = \Civi\Api4\CiviRulesRule::create(FALSE)
            ->setValues($ruleData)
            ->execute()->first();
        $action = 'created';
        $ruleId = $rule['id'];
    }
    
    // Add conditions
    foreach ($ruleDefinition['conditions'] as $conditionDef) {
        if (!isset($componentMappings['conditions'][$conditionDef['condition']])) {
            throw new Exception("Condition '{$conditionDef['condition']}' not found");
        }
        
        $conditionData = [
            'rule_id' => $ruleId,
            'condition_id' => $componentMappings['conditions'][$conditionDef['condition']],
            'condition_params' => json_encode(resolveParameters($conditionDef['parameters'], $config)),
        ];
        
        \Civi\Api4\CiviRulesRuleCondition::create(FALSE)
            ->setValues($conditionData)
            ->execute();
    }
    
    // Add actions
    foreach ($ruleDefinition['actions'] as $actionDef) {
        if (!isset($componentMappings['actions'][$actionDef['action']])) {
            throw new Exception("Action '{$actionDef['action']}' not found");
        }
        
        $actionData = [
            'rule_id' => $ruleId,
            'action_id' => $componentMappings['actions'][$actionDef['action']],
            'action_params' => json_encode(resolveParameters($actionDef['parameters'], $config)),
        ];
        
        \Civi\Api4\CiviRulesRuleAction::create(FALSE)
            ->setValues($actionData)
            ->execute();
    }
    
    return [
        'action' => $action,
        'rule_id' => $ruleId,
        'rule_data' => $rule,
    ];
}

/**
 * Resolve parameter references to actual values
 */
function resolveParameters($parameters, $config) {
    $resolved = [];
    
    foreach ($parameters as $key => $value) {
        if (is_array($value) && count($value) === 2) {
            // This is a reference to config [section, key]
            $section = $value[0];
            $configKey = $value[1];
            
            if (isset($config[$section][$configKey])) {
                $resolved[$key] = $config[$section][$configKey];
            } else {
                throw new Exception("Configuration reference not found: {$section}.{$configKey}");
            }
        } else {
            $resolved[$key] = $value;
        }
    }
    
    return $resolved;
}

/**
 * Get component information for debugging
 */
function debugComponentMappings() {
    echo "\n=== DEBUG: Available CiviRules Components ===\n";
    
    echo "\nTriggers:\n";
    $triggers = \Civi\Api4\CiviRulesTrigger::get(FALSE)->execute();
    foreach ($triggers as $trigger) {
        echo "- {$trigger['name']} (ID: {$trigger['id']}) - {$trigger['label']}\n";
    }
    
    echo "\nConditions:\n";
    $conditions = \Civi\Api4\CiviRulesCondition::get(FALSE)->execute();
    foreach ($conditions as $condition) {
        echo "- {$condition['name']} (ID: {$condition['id']}) - {$condition['label']}\n";
    }
    
    echo "\nActions:\n";
    $actions = \Civi\Api4\CiviRulesAction::get(FALSE)->execute();
    foreach ($actions as $action) {
        echo "- {$action['name']} (ID: {$action['id']}) - {$action['label']}\n";
    }
    
    echo "\n=== END DEBUG ===\n\n";
}

// Uncomment the line below to see all available components for debugging
// debugComponentMappings();

?>