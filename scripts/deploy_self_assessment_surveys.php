<?php

/**
 * MAS Self Assessment Survey Deployment Script
 * 
 * This script creates the complete Self Assessment Survey system:
 * - Activity types for Short and Full surveys
 * - Unified custom field group with all 35 questions
 * - Both Afforms (Short 21 questions, Full 35 questions)
 * 
 * IMPORTANT: Update the configuration section below for your target environment
 */

// ============================================================================
// ENVIRONMENT CONFIGURATION - UPDATE THESE VALUES FOR YOUR TARGET ENVIRONMENT
// ============================================================================

$config = [
    // Activity type values - these must be unique in your environment
    'sass_activity_type_value' => '1000',  // Short Self Assessment Survey
    'sasf_activity_type_value' => '1001',  // Full Self Assessment Survey
    
    // Server routes for the forms
    'sass_server_route' => 'civicrm/mas-sass-form',
    'sasf_server_route' => 'civicrm/mas-sasf-form',
    
    // Redirect URL after form submission
    'redirect_url' => 'https://masdemo.localhost/thank-you/',
    
    // Email confirmation template ID (set to null if not using)
    'email_confirmation_template_id' => null,
    
    // Form permissions
    'form_permissions' => ['*always allow*'],
    
    // Custom field group weight
    'custom_group_weight' => 10,
];

// ============================================================================
// DEPLOYMENT SCRIPT - DO NOT MODIFY BELOW THIS LINE
// ============================================================================

echo "=== MAS Self Assessment Survey Deployment Script ===\n";
echo "Starting deployment with configuration:\n";
print_r($config);
echo "\n";

$results = [];

try {
    // Step 1: Create Activity Types
    echo "Step 1: Creating Activity Types...\n";
    
    // Short Self Assessment Survey Activity Type
    $sassActivityType = \Civi\Api4\OptionValue::get(FALSE)
        ->addWhere('option_group_id.name', '=', 'activity_type')
        ->addWhere('value', '=', $config['sass_activity_type_value'])
        ->execute()->first();
    
    if (!$sassActivityType) {
        $sassActivityType = \Civi\Api4\OptionValue::create(FALSE)
            ->addValue('option_group_id', 2) // activity_type option group
            ->addValue('label', 'Short Self Assessment Survey')
            ->addValue('name', 'SASS')
            ->addValue('value', $config['sass_activity_type_value'])
            ->addValue('weight', (int)$config['sass_activity_type_value'])
            ->addValue('description', 'MAS Self Assessment Survey - Short Version (21 questions)')
            ->addValue('is_reserved', FALSE)
            ->addValue('is_active', TRUE)
            ->addValue('icon', 'fa-clipboard-check')
            ->execute()->first();
        echo "✓ Created Short Self Assessment Survey activity type (ID: {$sassActivityType['id']}, Value: {$config['sass_activity_type_value']})\n";
    } else {
        echo "✓ Short Self Assessment Survey activity type already exists (ID: {$sassActivityType['id']})\n";
    }
    $results['sass_activity_type_id'] = $sassActivityType['id'];
    
    // Full Self Assessment Survey Activity Type
    $sasfActivityType = \Civi\Api4\OptionValue::get(FALSE)
        ->addWhere('option_group_id.name', '=', 'activity_type')
        ->addWhere('value', '=', $config['sasf_activity_type_value'])
        ->execute()->first();
    
    if (!$sasfActivityType) {
        $sasfActivityType = \Civi\Api4\OptionValue::create(FALSE)
            ->addValue('option_group_id', 2) // activity_type option group
            ->addValue('label', 'Full Self Assessment Survey')
            ->addValue('name', 'SASF')
            ->addValue('value', $config['sasf_activity_type_value'])
            ->addValue('weight', (int)$config['sasf_activity_type_value'])
            ->addValue('description', 'MAS Self Assessment Survey - Full Version (35 questions)')
            ->addValue('is_reserved', FALSE)
            ->addValue('is_active', TRUE)
            ->addValue('icon', 'fa-clipboard-list')
            ->execute()->first();
        echo "✓ Created Full Self Assessment Survey activity type (ID: {$sasfActivityType['id']}, Value: {$config['sasf_activity_type_value']})\n";
    } else {
        echo "✓ Full Self Assessment Survey activity type already exists (ID: {$sasfActivityType['id']})\n";
    }
    $results['sasf_activity_type_id'] = $sasfActivityType['id'];
    
    // Step 2: Create Unified Custom Field Group
    echo "\nStep 2: Creating Unified Custom Field Group...\n";
    
    $customGroup = \Civi\Api4\CustomGroup::get(FALSE)
        ->addWhere('name', '=', 'Unified_Self_Assessment_Survey')
        ->execute()->first();
    
    if (!$customGroup) {
        $customGroup = \Civi\Api4\CustomGroup::create(FALSE)
            ->addValue('name', 'Unified_Self_Assessment_Survey')
            ->addValue('title', 'Self Assessment Survey')
            ->addValue('extends', 'Activity')
            ->addValue('extends_entity_column_value', [$config['sass_activity_type_value'], $config['sasf_activity_type_value']])
            ->addValue('style', 'Inline')
            ->addValue('collapse_display', FALSE)
            ->addValue('is_active', TRUE)
            ->addValue('is_public', TRUE)
            ->addValue('weight', $config['custom_group_weight'])
            ->execute()->first();
        echo "✓ Created unified custom field group (ID: {$customGroup['id']})\n";
    } else {
        echo "✓ Unified custom field group already exists (ID: {$customGroup['id']})\n";
    }
    $results['custom_group_id'] = $customGroup['id'];
    
    // Step 3: Create All 35 Survey Questions
    echo "\nStep 3: Creating Survey Questions...\n";
    
    $allQuestions = [
        // Mission and Vision (5 questions)
        ['name' => 'q01_mission_clear', 'label' => '1. Our mission is clear and understood by all staff and board members', 'weight' => 1, 'category' => 'Mission and Vision'],
        ['name' => 'q02_vision_inspiring', 'label' => '2. We have an inspiring vision that guides our work', 'weight' => 2, 'category' => 'Mission and Vision'],
        ['name' => 'q03_values_guide', 'label' => '3. Our organizational values clearly guide our decisions and actions', 'weight' => 3, 'category' => 'Mission and Vision'],
        ['name' => 'q04_mission_relevant', 'label' => '4. Our mission remains relevant to current community needs', 'weight' => 4, 'category' => 'Mission and Vision'],
        ['name' => 'q05_strategic_alignment', 'label' => '5. All our activities are clearly aligned with our mission', 'weight' => 5, 'category' => 'Mission and Vision'],
        
        // Governance (5 questions)
        ['name' => 'q06_board_effective', 'label' => '6. Our board is effective at providing governance and oversight', 'weight' => 6, 'category' => 'Governance'],
        ['name' => 'q07_roles_clear', 'label' => '7. Board and staff roles and responsibilities are clearly defined', 'weight' => 7, 'category' => 'Governance'],
        ['name' => 'q08_policies_current', 'label' => '8. We have current and comprehensive governance policies', 'weight' => 8, 'category' => 'Governance'],
        ['name' => 'q09_board_diverse', 'label' => '9. Our board reflects the diversity of our community', 'weight' => 9, 'category' => 'Governance'],
        ['name' => 'q10_board_recruitment', 'label' => '10. We have effective board recruitment and orientation processes', 'weight' => 10, 'category' => 'Governance'],
        
        // Financial Management (5 questions)
        ['name' => 'q11_financial_stable', 'label' => '11. Our organization is financially stable', 'weight' => 11, 'category' => 'Financial Management'],
        ['name' => 'q12_budget_process', 'label' => '12. We have a sound budgeting and financial planning process', 'weight' => 12, 'category' => 'Financial Management'],
        ['name' => 'q13_revenue_diverse', 'label' => '13. We have diversified revenue sources', 'weight' => 13, 'category' => 'Financial Management'],
        ['name' => 'q14_financial_controls', 'label' => '14. We have strong financial controls and accountability measures', 'weight' => 14, 'category' => 'Financial Management'],
        ['name' => 'q15_reserves_adequate', 'label' => '15. We maintain adequate financial reserves', 'weight' => 15, 'category' => 'Financial Management'],
        
        // Program Effectiveness (5 questions)
        ['name' => 'q16_programs_effective', 'label' => '16. Our programs are effective at achieving intended outcomes', 'weight' => 16, 'category' => 'Program Effectiveness'],
        ['name' => 'q17_data_collection', 'label' => '17. We regularly collect and analyze data on program performance', 'weight' => 17, 'category' => 'Program Effectiveness'],
        ['name' => 'q18_continuous_improvement', 'label' => '18. We use evaluation results for continuous program improvement', 'weight' => 18, 'category' => 'Program Effectiveness'],
        ['name' => 'q19_program_innovation', 'label' => '19. We regularly innovate and adapt our programs', 'weight' => 19, 'category' => 'Program Effectiveness'],
        ['name' => 'q20_impact_measurement', 'label' => '20. We effectively measure and communicate our impact', 'weight' => 20, 'category' => 'Program Effectiveness'],
        
        // Human Resources (5 questions)
        ['name' => 'q21_staff_skilled', 'label' => '21. Our staff have the skills and resources needed to do their jobs well', 'weight' => 21, 'category' => 'Human Resources'],
        ['name' => 'q22_professional_development', 'label' => '22. We provide adequate professional development opportunities', 'weight' => 22, 'category' => 'Human Resources'],
        ['name' => 'q23_succession_planning', 'label' => '23. We have effective succession planning and knowledge management', 'weight' => 23, 'category' => 'Human Resources'],
        ['name' => 'q24_compensation_competitive', 'label' => '24. Our compensation and benefits are competitive', 'weight' => 24, 'category' => 'Human Resources'],
        ['name' => 'q25_performance_management', 'label' => '25. We have effective performance management systems', 'weight' => 25, 'category' => 'Human Resources'],
        
        // Organizational Culture (5 questions)
        ['name' => 'q26_communication_open', 'label' => '26. We have open and effective internal communication', 'weight' => 26, 'category' => 'Organizational Culture'],
        ['name' => 'q27_culture_positive', 'label' => '27. Our organizational culture is positive and supportive', 'weight' => 27, 'category' => 'Organizational Culture'],
        ['name' => 'q28_change_adaptable', 'label' => '28. We are adaptable and responsive to change', 'weight' => 28, 'category' => 'Organizational Culture'],
        ['name' => 'q29_collaboration_strong', 'label' => '29. We have strong collaboration across departments/programs', 'weight' => 29, 'category' => 'Organizational Culture'],
        ['name' => 'q30_learning_culture', 'label' => '30. We have a culture of learning and continuous improvement', 'weight' => 30, 'category' => 'Organizational Culture'],
        
        // External Relations (5 questions)
        ['name' => 'q31_stakeholder_engaged', 'label' => '31. We effectively engage with our key stakeholders', 'weight' => 31, 'category' => 'External Relations'],
        ['name' => 'q32_partnerships_strong', 'label' => '32. We have strong partnerships that advance our mission', 'weight' => 32, 'category' => 'External Relations'],
        ['name' => 'q33_reputation_positive', 'label' => '33. We have a positive reputation in our community', 'weight' => 33, 'category' => 'External Relations'],
        ['name' => 'q34_marketing_effective', 'label' => '34. Our marketing and communications are effective', 'weight' => 34, 'category' => 'External Relations'],
        ['name' => 'q35_advocacy_engaged', 'label' => '35. We effectively engage in advocacy and policy work when appropriate', 'weight' => 35, 'category' => 'External Relations']
    ];
    
    $createdFields = 0;
    $existingFields = 0;
    
    foreach ($allQuestions as $question) {
        // Check if field already exists
        $existingField = \Civi\Api4\CustomField::get(FALSE)
            ->addWhere('custom_group_id', '=', $customGroup['id'])
            ->addWhere('name', '=', $question['name'])
            ->execute()->first();
        
        if (!$existingField) {
            // Create the custom field
            $field = \Civi\Api4\CustomField::create(FALSE)
                ->addValue('custom_group_id', $customGroup['id'])
                ->addValue('name', $question['name'])
                ->addValue('label', $question['label'])
                ->addValue('data_type', 'String')
                ->addValue('html_type', 'Radio')
                ->addValue('is_required', TRUE)
                ->addValue('is_active', TRUE)
                ->addValue('weight', $question['weight'])
                ->addValue('help_pre', 'Category: ' . $question['category'])
                ->execute()->first();
            
            // Create option group for 1-5 scale
            $optionGroup = \Civi\Api4\OptionGroup::create(FALSE)
                ->addValue('name', 'unified_sas_' . $question['name'])
                ->addValue('title', 'SAS: ' . $question['label'])
                ->addValue('description', 'Rating scale for Self Assessment Survey question')
                ->addValue('is_active', TRUE)
                ->execute()->first();
            
            // Create option values 1-5
            $scaleLabels = [
                1 => 'Strongly Disagree',
                2 => 'Disagree',
                3 => 'Neutral', 
                4 => 'Agree',
                5 => 'Strongly Agree'
            ];
            
            foreach ($scaleLabels as $value => $label) {
                \Civi\Api4\OptionValue::create(FALSE)
                    ->addValue('option_group_id', $optionGroup['id'])
                    ->addValue('label', $label)
                    ->addValue('value', $value)
                    ->addValue('weight', $value)
                    ->addValue('is_active', TRUE)
                    ->execute();
            }
            
            // Update field to use the option group
            \Civi\Api4\CustomField::update(FALSE)
                ->addWhere('id', '=', $field['id'])
                ->addValue('option_group_id', $optionGroup['id'])
                ->execute();
            
            $createdFields++;
        } else {
            $existingFields++;
        }
    }
    
    echo "✓ Created $createdFields new survey questions, $existingFields already existed\n";
    
    // Step 4: Create Short Self Assessment Survey Afform
    echo "\nStep 4: Creating Short Self Assessment Survey Afform...\n";
    
    $sassAfform = \Civi\Api4\Afform::get(FALSE)
        ->addWhere('name', '=', 'afformMASSASS')
        ->execute()->first();
    
    if (!$sassAfform) {
        // Create SASS Afform
        $sassAfformData = createSASSAfform($config, $customGroup['name']);
        $sassAfform = \Civi\Api4\Afform::create(FALSE)
            ->setValues($sassAfformData)
            ->execute()->first();
        echo "✓ Created Short Self Assessment Survey Afform\n";
    } else {
        echo "✓ Short Self Assessment Survey Afform already exists\n";
    }
    
    // Step 5: Create Full Self Assessment Survey Afform
    echo "\nStep 5: Creating Full Self Assessment Survey Afform...\n";
    
    $sasfAfform = \Civi\Api4\Afform::get(FALSE)
        ->addWhere('name', '=', 'afformMASSASF')
        ->execute()->first();
    
    if (!$sasfAfform) {
        // Create SASF Afform  
        $sasfAfformData = createSASFAfform($config, $customGroup['name']);
        $sasfAfform = \Civi\Api4\Afform::create(FALSE)
            ->setValues($sasfAfformData)
            ->execute()->first();
        echo "✓ Created Full Self Assessment Survey Afform\n";
    } else {
        echo "✓ Full Self Assessment Survey Afform already exists\n";
    }
    
    echo "\n=== DEPLOYMENT COMPLETED SUCCESSFULLY ===\n";
    echo "Summary:\n";
    echo "- Short Survey Activity Type: ID {$results['sass_activity_type_id']} (Value: {$config['sass_activity_type_value']})\n";
    echo "- Full Survey Activity Type: ID {$results['sasf_activity_type_id']} (Value: {$config['sasf_activity_type_value']})\n";  
    echo "- Custom Field Group: ID {$results['custom_group_id']}\n";
    echo "- Short Survey Form: {$config['sass_server_route']}\n";
    echo "- Full Survey Form: {$config['sasf_server_route']}\n";
    echo "\nDon't forget to flush the CiviCRM cache!\n";
    
} catch (Exception $e) {
    echo "\n❌ DEPLOYMENT FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Helper function to create SASS Afform data structure
function createSASSAfform($config, $customGroupName) {
    $shortSurveyFields = [
        'q01_mission_clear', 'q02_vision_inspiring', 'q03_values_guide',
        'q06_board_effective', 'q07_roles_clear', 'q08_policies_current',
        'q11_financial_stable', 'q12_budget_process', 'q13_revenue_diverse',
        'q16_programs_effective', 'q17_data_collection', 'q18_continuous_improvement',
        'q21_staff_skilled', 'q22_professional_development', 'q23_succession_planning',
        'q26_communication_open', 'q27_culture_positive', 'q28_change_adaptable',
        'q31_stakeholder_engaged', 'q32_partnerships_strong', 'q33_reputation_positive'
    ];
    
    return [
        'name' => 'afformMASSASS',
        'type' => 'form',
        'title' => 'MAS Short Self Assessment Survey',
        'description' => 'MAS Self Assessment Survey - Short Version (21 questions)',
        'placement' => ['msg_token_single'],
        'icon' => 'fa-clipboard-check',
        'server_route' => $config['sass_server_route'],
        'is_public' => true,
        'permission' => $config['form_permissions'],
        'permission_operator' => 'AND',
        'redirect' => $config['redirect_url'],
        'submit_enabled' => true,
        'create_submission' => true,
        'manual_processing' => false,
        'allow_verification_by_email' => false,
        'email_confirmation_template_id' => $config['email_confirmation_template_id'],
        'autosave_draft' => false,
        'layout' => [createFormLayout($config['sass_activity_type_value'], $customGroupName, $shortSurveyFields, 'Short Self Assessment Survey', 'Short Version: 21 Questions')]
    ];
}

// Helper function to create SASF Afform data structure  
function createSASFAfform($config, $customGroupName) {
    $allSurveyFields = [
        'q01_mission_clear', 'q02_vision_inspiring', 'q03_values_guide', 'q04_mission_relevant', 'q05_strategic_alignment',
        'q06_board_effective', 'q07_roles_clear', 'q08_policies_current', 'q09_board_diverse', 'q10_board_recruitment',
        'q11_financial_stable', 'q12_budget_process', 'q13_revenue_diverse', 'q14_financial_controls', 'q15_reserves_adequate',
        'q16_programs_effective', 'q17_data_collection', 'q18_continuous_improvement', 'q19_program_innovation', 'q20_impact_measurement',
        'q21_staff_skilled', 'q22_professional_development', 'q23_succession_planning', 'q24_compensation_competitive', 'q25_performance_management',
        'q26_communication_open', 'q27_culture_positive', 'q28_change_adaptable', 'q29_collaboration_strong', 'q30_learning_culture',
        'q31_stakeholder_engaged', 'q32_partnerships_strong', 'q33_reputation_positive', 'q34_marketing_effective', 'q35_advocacy_engaged'
    ];
    
    return [
        'name' => 'afformMASSASF',
        'type' => 'form',
        'title' => 'MAS Full Self Assessment Survey',
        'description' => 'MAS Self Assessment Survey - Full Version (35 questions)',
        'placement' => ['msg_token_single'],
        'icon' => 'fa-clipboard-list',
        'server_route' => $config['sasf_server_route'],
        'is_public' => true,
        'permission' => $config['form_permissions'],
        'permission_operator' => 'AND',
        'redirect' => $config['redirect_url'],
        'submit_enabled' => true,
        'create_submission' => true,
        'manual_processing' => false,
        'allow_verification_by_email' => false,
        'email_confirmation_template_id' => $config['email_confirmation_template_id'],
        'autosave_draft' => false,
        'layout' => [createFormLayout($config['sasf_activity_type_value'], $customGroupName, $allSurveyFields, 'Full Self Assessment Survey', 'Full Version: 35 Questions')]
    ];
}

// Helper function to create form layout
function createFormLayout($activityTypeValue, $customGroupName, $surveyFields, $surveyTitle, $surveyDescription) {
    $surveyFieldElements = [];
    
    foreach ($surveyFields as $fieldName) {
        $surveyFieldElements[] = ['#text' => "\n        "];
        $surveyFieldElements[] = [
            '#tag' => 'af-field',
            'name' => $customGroupName . '.' . $fieldName,
            'defn' => ['required' => true, 'input_attrs' => []]
        ];
    }
    $surveyFieldElements[] = ['#text' => "\n      "];
    
    return [
        '#tag' => 'af-form',
        'ctrl' => 'afform',
        '#children' => [
            ['#text' => "\n  "],
            
            // Organization entity
            [
                '#tag' => 'af-entity',
                'data' => ['source' => $surveyTitle],
                'type' => 'Organization',
                'name' => 'Organization1',
                'label' => 'Organization 1',
                'actions' => ['create' => false, 'update' => true],
                'security' => 'FBAC',
                'url-autofill' => '0',
                'autofill' => 'relationship:Employer of',
                'autofill-relationship' => 'Individual1',
                'contact-dedupe' => 'Organization.Unsupervised'
            ],
            
            ['#text' => "\n  "],
            
            // Individual entity
            [
                '#tag' => 'af-entity',
                'data' => ['source' => $surveyTitle],
                'type' => 'Individual',
                'name' => 'Individual1', 
                'label' => 'Individual 1',
                'actions' => ['create' => true, 'update' => true],
                'security' => 'FBAC',
                'autofill' => 'entity_id',
                'contact-dedupe' => 'Individual.Supervised'
            ],
            
            ['#text' => "\n  "],
            
            // Activity entity
            [
                '#tag' => 'af-entity',
                'data' => [
                    'source_contact_id' => 'Individual1',
                    'activity_type_id' => (int)$activityTypeValue,
                    'status_id' => 2 // Completed
                ],
                'type' => 'Activity',
                'name' => 'Activity1',
                'label' => 'Activity 1', 
                'actions' => ['create' => true, 'update' => false],
                'security' => 'FBAC'
            ],
            
            ['#text' => "\n  "],
            
            // Main form container
            [
                '#tag' => 'div',
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n    "],
                    
                    // Organization fieldset
                    [
                        '#tag' => 'fieldset',
                        'af-fieldset' => 'Organization1',
                        'class' => 'af-container af-container-style-pane',
                        'af-title' => 'Organization Information',
                        'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
                        '#children' => [
                            ['#text' => "\n      "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'organization_name',
                                'defn' => ['required' => true, 'input_attrs' => []]
                            ],
                            ['#text' => "\n    "]
                        ]
                    ],
                    
                    ['#text' => "\n    "],
                    
                    // Individual fieldset
                    [
                        '#tag' => 'fieldset',
                        'af-fieldset' => 'Individual1',
                        'class' => 'af-container af-container-style-pane',
                        'af-title' => 'Contact Information',
                        'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
                        '#children' => [
                            ['#text' => "\n      "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'first_name',
                                'defn' => ['required' => true, 'input_attrs' => []]
                            ],
                            ['#text' => "\n      "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'last_name',
                                'defn' => ['required' => true, 'input_attrs' => []]
                            ],
                            ['#text' => "\n      "],
                            [
                                '#tag' => 'div',
                                'af-join' => 'Email',
                                'actions' => ['update' => true, 'delete' => true],
                                'data' => ['is_primary' => true],
                                '#children' => [
                                    ['#text' => "\n        "],
                                    [
                                        '#tag' => 'af-field',
                                        'name' => 'email',
                                        'defn' => ['required' => true, 'input_attrs' => []]
                                    ],
                                    ['#text' => "\n      "]
                                ]
                            ],
                            ['#text' => "\n    "]
                        ]
                    ],
                    
                    ['#text' => "\n    "],
                    
                    // Survey fieldset
                    [
                        '#tag' => 'fieldset',
                        'af-fieldset' => 'Activity1',
                        'class' => 'af-container af-container-style-pane',
                        'af-title' => $surveyTitle,
                        'style' => 'border: 3px solid #617de6; background-color: #ffffff',
                        '#children' => array_merge([
                            ['#text' => "\n      "],
                            [
                                '#tag' => 'div',
                                'class' => 'af-markup',
                                '#children' => [
                                    ['#text' => "\n        "],
                                    [
                                        '#tag' => 'p',
                                        '#children' => [
                                            ['#text' => 'Please rate each statement using the scale: 1 = Strongly Disagree, 2 = Disagree, 3 = Neutral, 4 = Agree, 5 = Strongly Agree']
                                        ]
                                    ],
                                    ['#text' => "\n        "],
                                    [
                                        '#tag' => 'p',
                                        '#children' => [
                                            ['#text' => '<strong>' . $surveyDescription . '</strong>']
                                        ]
                                    ],
                                    ['#text' => "\n      "]
                                ]
                            ]
                        ], $surveyFieldElements)
                    ],
                    
                    ['#text' => "\n    "],
                    
                    // Submit button
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n      "],
                            [
                                '#tag' => 'button',
                                'class' => 'af-button btn btn-primary',
                                'crm-icon' => 'fa-check',
                                'ng-click' => 'afform.submit()',
                                'ng-if' => 'afform.showSubmitButton',
                                '#children' => [['#text' => 'Submit Survey']]
                            ],
                            ['#text' => "\n    "]
                        ]
                    ],
                    
                    ['#text' => "\n  "]
                ]
            ],
            
            ['#text' => "\n"]
        ]
    ];
}

?>