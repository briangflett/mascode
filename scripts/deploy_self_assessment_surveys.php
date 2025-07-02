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
    
    // Step 4: Create or Update Short Self Assessment Survey Afform
    echo "\nStep 4: Creating or Updating Short Self Assessment Survey Afform...\n";
    
    $sassAfform = \Civi\Api4\Afform::get(FALSE)
        ->addWhere('name', '=', 'afformMASSASS')
        ->execute()->first();
    
    // Create or update SASS Afform
    $sassAfformData = createSASSAfform($config, $customGroup['name']);
    
    if ($sassAfform) {
        // Update existing form
        $sassAfform = \Civi\Api4\Afform::update(FALSE)
            ->addWhere('name', '=', 'afformMASSASS')
            ->setValues($sassAfformData)
            ->execute()->first();
        echo "✓ Updated Short Self Assessment Survey Afform\n";
    } else {
        // Create new form
        $sassAfform = \Civi\Api4\Afform::create(FALSE)
            ->setValues($sassAfformData)
            ->execute()->first();
        echo "✓ Created Short Self Assessment Survey Afform\n";
    }
    
    // Step 5: Create or Update Full Self Assessment Survey Afform
    echo "\nStep 5: Creating or Updating Full Self Assessment Survey Afform...\n";
    
    $sasfAfform = \Civi\Api4\Afform::get(FALSE)
        ->addWhere('name', '=', 'afformMASSASF')
        ->execute()->first();
    
    // Create or update SASF Afform
    $sasfAfformData = createSASFAfform($config, $customGroup['name']);
    
    if ($sasfAfform) {
        // Update existing form
        $sasfAfform = \Civi\Api4\Afform::update(FALSE)
            ->addWhere('name', '=', 'afformMASSASF')
            ->setValues($sasfAfformData)
            ->execute()->first();
        echo "✓ Updated Full Self Assessment Survey Afform\n";
    } else {
        // Create new form
        $sasfAfform = \Civi\Api4\Afform::create(FALSE)
            ->setValues($sasfAfformData)
            ->execute()->first();
        echo "✓ Created Full Self Assessment Survey Afform\n";
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
        'layout' => [createSASSFormLayout()]
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
        'layout' => [createSASFFormLayout()]
    ];
}

// Helper function to create form layout
// Helper function to create SASS form layout
function createSASSFormLayout() {
    return array (
  '#tag' => 'af-form',
  'ctrl' => 'afform',
  '#children' => 
  array (
    0 => 
    array (
      '#text' => '
  ',
    ),
    1 => 
    array (
      '#tag' => 'af-entity',
      'data' => 
      array (
        'source' => 'Short Self Assessment Survey',
      ),
      'type' => 'Organization',
      'name' => 'Organization1',
      'label' => 'Organization 1',
      'actions' => 
      array (
        'create' => false,
        'update' => true,
      ),
      'security' => 'FBAC',
      'url-autofill' => '0',
      'autofill' => 'relationship:Employer of',
      'autofill-relationship' => 'Individual1',
      'contact-dedupe' => 'Organization.Unsupervised',
    ),
    2 => 
    array (
      '#text' => '
  ',
    ),
    3 => 
    array (
      '#tag' => 'af-entity',
      'data' => 
      array (
        'source' => 'Short Self Assessment Survey',
      ),
      'type' => 'Individual',
      'name' => 'Individual1',
      'label' => 'Individual 1',
      'actions' => 
      array (
        'create' => true,
        'update' => true,
      ),
      'security' => 'FBAC',
      'autofill' => 'entity_id',
      'contact-dedupe' => 'Individual.Supervised',
    ),
    4 => 
    array (
      '#text' => '
  ',
    ),
    5 => 
    array (
      '#tag' => 'af-entity',
      'data' => 
      array (
        'source_contact_id' => 'Individual1',
        'activity_type_id' => 1000,
        'status_id' => 2,
      ),
      'type' => 'Activity',
      'name' => 'Activity1',
      'label' => 'Activity 1',
      'actions' => 
      array (
        'create' => true,
        'update' => false,
      ),
      'security' => 'FBAC',
    ),
    6 => 
    array (
      '#text' => '
  ',
    ),
    7 => 
    array (
      '#tag' => 'div',
      'class' => 'af-container',
      '#children' => 
      array (
        0 => 
        array (
          '#text' => '
    ',
        ),
        1 => 
        array (
          '#tag' => 'fieldset',
          'af-fieldset' => 'Organization1',
          'class' => 'af-container af-container-style-pane',
          'af-title' => 'Organization Information',
          'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
      ',
            ),
            1 => 
            array (
              '#tag' => 'div',
              'class' => 'af-container af-layout-inline',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        ',
                ),
                1 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'organization_name',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      ',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
    ',
            ),
          ),
        ),
        2 => 
        array (
          '#text' => '
    ',
        ),
        3 => 
        array (
          '#tag' => 'fieldset',
          'af-fieldset' => 'Individual1',
          'class' => 'af-container af-container-style-pane',
          'af-title' => 'Contact Information',
          'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
      ',
            ),
            1 => 
            array (
              '#tag' => 'div',
              'class' => 'af-container af-layout-inline',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        ',
                ),
                1 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'first_name',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        ',
                ),
                3 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'last_name',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                4 => 
                array (
                  '#text' => '
        ',
                ),
                5 => 
                array (
                  '#tag' => 'div',
                  'af-join' => 'Email',
                  'actions' => 
                  array (
                    'update' => true,
                    'delete' => true,
                  ),
                  'data' => 
                  array (
                    'is_primary' => true,
                  ),
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => '
          ',
                    ),
                    1 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'email',
                      'defn' => 
                      array (
                        'required' => true,
                        'input_attrs' => 
                        array (
                        ),
                      ),
                    ),
                    2 => 
                    array (
                      '#text' => '
        ',
                    ),
                  ),
                ),
                6 => 
                array (
                  '#text' => '
      ',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
    ',
            ),
          ),
        ),
        4 => 
        array (
          '#text' => '
    ',
        ),
        5 => 
        array (
          '#tag' => 'fieldset',
          'af-fieldset' => 'Activity1',
          'class' => 'af-container af-container-style-pane',
          'af-title' => 'Short Self Assessment Survey',
          'style' => 'border: 3px solid #617de6; background-color: #ffffff',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
      ',
            ),
            1 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'p',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Please rate each statement using the scale: 1 = Strongly Disagree, 2 = Disagree, 3 = Neutral, 4 = Agree, 5 = Strongly Agree',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        ',
                ),
                3 => 
                array (
                  '#tag' => 'p',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#tag' => 'strong',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => 'Short Version: 21 Questions',
                        ),
                      ),
                    ),
                  ),
                ),
                4 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
      ',
            ),
            3 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px; margin-bottom: 15px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Mission and Vision',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            4 => 
            array (
              '#text' => '
      ',
            ),
            5 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q01_mission_clear',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            6 => 
            array (
              '#text' => '
      ',
            ),
            7 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q02_vision_inspiring',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            8 => 
            array (
              '#text' => '
      ',
            ),
            9 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q03_values_guide',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            10 => 
            array (
              '#text' => '
      ',
            ),
            11 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px; margin-bottom: 15px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Governance',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            12 => 
            array (
              '#text' => '
      ',
            ),
            13 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q06_board_effective',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            14 => 
            array (
              '#text' => '
      ',
            ),
            15 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q07_roles_clear',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            16 => 
            array (
              '#text' => '
      ',
            ),
            17 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q08_policies_current',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            18 => 
            array (
              '#text' => '
      ',
            ),
            19 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px; margin-bottom: 15px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Financial Management',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            20 => 
            array (
              '#text' => '
      ',
            ),
            21 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q11_financial_stable',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            22 => 
            array (
              '#text' => '
      ',
            ),
            23 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q12_budget_process',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            24 => 
            array (
              '#text' => '
      ',
            ),
            25 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q13_revenue_diverse',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            26 => 
            array (
              '#text' => '
      ',
            ),
            27 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px; margin-bottom: 15px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Program Effectiveness',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            28 => 
            array (
              '#text' => '
      ',
            ),
            29 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q16_programs_effective',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            30 => 
            array (
              '#text' => '
      ',
            ),
            31 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q17_data_collection',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            32 => 
            array (
              '#text' => '
      ',
            ),
            33 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q18_continuous_improvement',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            34 => 
            array (
              '#text' => '
      ',
            ),
            35 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px; margin-bottom: 15px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Human Resources',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            36 => 
            array (
              '#text' => '
      ',
            ),
            37 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q21_staff_skilled',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            38 => 
            array (
              '#text' => '
      ',
            ),
            39 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q22_professional_development',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            40 => 
            array (
              '#text' => '
      ',
            ),
            41 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q23_succession_planning',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            42 => 
            array (
              '#text' => '
      ',
            ),
            43 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px; margin-bottom: 15px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Organizational Culture',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            44 => 
            array (
              '#text' => '
      ',
            ),
            45 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q26_communication_open',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            46 => 
            array (
              '#text' => '
      ',
            ),
            47 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q27_culture_positive',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            48 => 
            array (
              '#text' => '
      ',
            ),
            49 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q28_change_adaptable',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            50 => 
            array (
              '#text' => '
      ',
            ),
            51 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px; margin-bottom: 15px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'External Relations',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            52 => 
            array (
              '#text' => '
      ',
            ),
            53 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q31_stakeholder_engaged',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            54 => 
            array (
              '#text' => '
      ',
            ),
            55 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q32_partnerships_strong',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            56 => 
            array (
              '#text' => '
      ',
            ),
            57 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q33_reputation_positive',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            58 => 
            array (
              '#text' => '
    ',
            ),
          ),
        ),
        6 => 
        array (
          '#text' => '
    ',
        ),
        7 => 
        array (
          '#tag' => 'div',
          'class' => 'af-container af-layout-inline',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
      ',
            ),
            1 => 
            array (
              '#tag' => 'button',
              'class' => 'af-button btn btn-primary',
              'crm-icon' => 'fa-check',
              'ng-click' => 'afform.submit()',
              'ng-if' => 'afform.showSubmitButton',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => 'Submit Survey',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
    ',
            ),
          ),
        ),
        8 => 
        array (
          '#text' => '
  ',
        ),
      ),
    ),
    8 => 
    array (
      '#text' => '
',
    ),
  ),
);
}

// Helper function to create SASF form layout
function createSASFFormLayout() {
    return array (
  '#tag' => 'af-form',
  'ctrl' => 'afform',
  '#children' => 
  array (
    0 => 
    array (
      '#text' => '
  ',
    ),
    1 => 
    array (
      '#tag' => 'af-entity',
      'data' => 
      array (
        'source' => 'Full Self Assessment Survey',
      ),
      'type' => 'Organization',
      'name' => 'Organization1',
      'label' => 'Organization 1',
      'actions' => 
      array (
        'create' => false,
        'update' => true,
      ),
      'security' => 'FBAC',
      'url-autofill' => '0',
      'autofill' => 'relationship:Employer of',
      'autofill-relationship' => 'Individual1',
      'contact-dedupe' => 'Organization.Unsupervised',
    ),
    2 => 
    array (
      '#text' => '
  ',
    ),
    3 => 
    array (
      '#tag' => 'af-entity',
      'data' => 
      array (
        'source' => 'Full Self Assessment Survey',
      ),
      'type' => 'Individual',
      'name' => 'Individual1',
      'label' => 'Individual 1',
      'actions' => 
      array (
        'create' => true,
        'update' => true,
      ),
      'security' => 'FBAC',
      'autofill' => 'entity_id',
      'contact-dedupe' => 'Individual.Supervised',
    ),
    4 => 
    array (
      '#text' => '
  ',
    ),
    5 => 
    array (
      '#tag' => 'af-entity',
      'data' => 
      array (
        'source_contact_id' => 'Individual1',
        'activity_type_id' => 74,
        'status_id' => 2,
      ),
      'type' => 'Activity',
      'name' => 'Activity1',
      'label' => 'Activity 1',
      'actions' => 
      array (
        'create' => true,
        'update' => false,
      ),
      'security' => 'FBAC',
    ),
    6 => 
    array (
      '#text' => '
  ',
    ),
    7 => 
    array (
      '#tag' => 'div',
      'class' => 'af-container',
      '#children' => 
      array (
        0 => 
        array (
          '#text' => '
    ',
        ),
        1 => 
        array (
          '#tag' => 'fieldset',
          'af-fieldset' => 'Organization1',
          'class' => 'af-container af-container-style-pane',
          'af-title' => 'Organization Information',
          'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
      ',
            ),
            1 => 
            array (
              '#tag' => 'div',
              'class' => 'af-container af-layout-inline',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        ',
                ),
                1 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'organization_name',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      ',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
    ',
            ),
          ),
        ),
        2 => 
        array (
          '#text' => '
    ',
        ),
        3 => 
        array (
          '#tag' => 'fieldset',
          'af-fieldset' => 'Individual1',
          'class' => 'af-container af-container-style-pane',
          'af-title' => 'Contact Information',
          'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
      ',
            ),
            1 => 
            array (
              '#tag' => 'div',
              'class' => 'af-container af-layout-inline',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        ',
                ),
                1 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'first_name',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        ',
                ),
                3 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'last_name',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                4 => 
                array (
                  '#text' => '
        ',
                ),
                5 => 
                array (
                  '#tag' => 'div',
                  'af-join' => 'Email',
                  'actions' => 
                  array (
                    'update' => true,
                    'delete' => true,
                  ),
                  'data' => 
                  array (
                    'is_primary' => true,
                  ),
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => '
          ',
                    ),
                    1 => 
                    array (
                      '#tag' => 'div',
                      'actions' => 
                      array (
                        'update' => true,
                        'delete' => true,
                      ),
                      'class' => 'af-container',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => '
            ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'div',
                          'class' => 'af-container af-layout-inline',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => '
              ',
                            ),
                            1 => 
                            array (
                              '#tag' => 'af-field',
                              'name' => 'email',
                              'defn' => 
                              array (
                                'required' => true,
                                'input_attrs' => 
                                array (
                                ),
                              ),
                            ),
                            2 => 
                            array (
                              '#text' => '
              ',
                            ),
                            3 => 
                            array (
                              '#tag' => 'af-field',
                              'name' => 'location_type_id',
                              'defn' => 
                              array (
                                'afform_default' => '1',
                                'input_attrs' => 
                                array (
                                ),
                                'required' => false,
                                'label' => false,
                                'input_type' => 'Hidden',
                              ),
                            ),
                            4 => 
                            array (
                              '#text' => '
              ',
                            ),
                            5 => 
                            array (
                              '#tag' => 'af-field',
                              'name' => 'is_primary',
                              'defn' => 
                              array (
                                'afform_default' => '1',
                                'label' => false,
                                'input_type' => 'Hidden',
                              ),
                            ),
                            6 => 
                            array (
                              '#text' => '
            ',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => '
          ',
                        ),
                      ),
                    ),
                    2 => 
                    array (
                      '#text' => '
        ',
                    ),
                  ),
                ),
                6 => 
                array (
                  '#text' => '
      ',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
    ',
            ),
          ),
        ),
        4 => 
        array (
          '#text' => '
    ',
        ),
        5 => 
        array (
          '#tag' => 'fieldset',
          'af-fieldset' => 'Activity1',
          'class' => 'af-container af-container-style-pane',
          'af-title' => 'Self Assessment Survey - Full Version',
          'style' => 'border: 3px solid #617de6; background-color: #ffffff',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
      ',
            ),
            1 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
        ',
                ),
                1 => 
                array (
                  '#tag' => 'p',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Please rate each statement using the scale: 1 = Strongly Disagree, 2 = Disagree, 3 = Neutral, 4 = Agree, 5 = Strongly Agree',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        ',
                ),
                3 => 
                array (
                  '#tag' => 'p',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#tag' => 'strong',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => 'Full Version: 35 Questions',
                        ),
                      ),
                    ),
                    1 => 
                    array (
                      '#text' => ' (comprehensive assessment covering all 6 organizational areas with 5 questions each, plus 5 additional questions)',
                    ),
                  ),
                ),
                4 => 
                array (
                  '#text' => '
      
      ',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
      ',
            ),
            3 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
          ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Mission and Vision',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        
      ',
                ),
              ),
            ),
            4 => 
            array (
              '#text' => '
      ',
            ),
            5 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q01_mission_clear',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
                'help_pre' => NULL,
              ),
            ),
            6 => 
            array (
              '#text' => '
      ',
            ),
            7 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q02_vision_inspiring',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            8 => 
            array (
              '#text' => '
      ',
            ),
            9 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q03_values_guide',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            10 => 
            array (
              '#text' => '
      ',
            ),
            11 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q04_mission_relevant',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            12 => 
            array (
              '#text' => '
      ',
            ),
            13 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q05_strategic_alignment',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            14 => 
            array (
              '#text' => '
      ',
            ),
            15 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
          ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Governance',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        
      ',
                ),
              ),
            ),
            16 => 
            array (
              '#text' => '
      ',
            ),
            17 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q06_board_effective',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            18 => 
            array (
              '#text' => '
      ',
            ),
            19 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q07_roles_clear',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            20 => 
            array (
              '#text' => '
      ',
            ),
            21 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q08_policies_current',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            22 => 
            array (
              '#text' => '
      ',
            ),
            23 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q09_board_diverse',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            24 => 
            array (
              '#text' => '
      ',
            ),
            25 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q10_board_recruitment',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            26 => 
            array (
              '#text' => '
      ',
            ),
            27 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
          ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Financial Management',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        
      ',
                ),
              ),
            ),
            28 => 
            array (
              '#text' => '
      ',
            ),
            29 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q11_financial_stable',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            30 => 
            array (
              '#text' => '
      ',
            ),
            31 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q12_budget_process',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            32 => 
            array (
              '#text' => '
      ',
            ),
            33 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q13_revenue_diverse',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            34 => 
            array (
              '#text' => '
      ',
            ),
            35 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q14_financial_controls',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            36 => 
            array (
              '#text' => '
      ',
            ),
            37 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q15_reserves_adequate',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            38 => 
            array (
              '#text' => '
      ',
            ),
            39 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
          ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Program Effectiveness',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        
      ',
                ),
              ),
            ),
            40 => 
            array (
              '#text' => '
      ',
            ),
            41 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q16_programs_effective',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            42 => 
            array (
              '#text' => '
      ',
            ),
            43 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q17_data_collection',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            44 => 
            array (
              '#text' => '
      ',
            ),
            45 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q18_continuous_improvement',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            46 => 
            array (
              '#text' => '
      ',
            ),
            47 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q19_program_innovation',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            48 => 
            array (
              '#text' => '
      ',
            ),
            49 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q20_impact_measurement',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            50 => 
            array (
              '#text' => '
      ',
            ),
            51 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
          ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Human Resources',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        
      ',
                ),
              ),
            ),
            52 => 
            array (
              '#text' => '
      ',
            ),
            53 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q21_staff_skilled',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            54 => 
            array (
              '#text' => '
      ',
            ),
            55 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q22_professional_development',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            56 => 
            array (
              '#text' => '
      ',
            ),
            57 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q23_succession_planning',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            58 => 
            array (
              '#text' => '
      ',
            ),
            59 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q24_compensation_competitive',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            60 => 
            array (
              '#text' => '
      ',
            ),
            61 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q25_performance_management',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            62 => 
            array (
              '#text' => '
      ',
            ),
            63 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
          ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Organizational Culture',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        
      ',
                ),
              ),
            ),
            64 => 
            array (
              '#text' => '
      ',
            ),
            65 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q26_communication_open',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            66 => 
            array (
              '#text' => '
      ',
            ),
            67 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q27_culture_positive',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            68 => 
            array (
              '#text' => '
      ',
            ),
            69 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q28_change_adaptable',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            70 => 
            array (
              '#text' => '
      ',
            ),
            71 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q29_collaboration_strong',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            72 => 
            array (
              '#text' => '
      ',
            ),
            73 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q30_learning_culture',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            74 => 
            array (
              '#text' => '
      ',
            ),
            75 => 
            array (
              '#tag' => 'div',
              'class' => 'af-markup',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        
          ',
                ),
                1 => 
                array (
                  '#tag' => 'h4',
                  'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'External Relations',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        
      ',
                ),
              ),
            ),
            76 => 
            array (
              '#text' => '
      ',
            ),
            77 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q31_stakeholder_engaged',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            78 => 
            array (
              '#text' => '
      ',
            ),
            79 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q32_partnerships_strong',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            80 => 
            array (
              '#text' => '
      ',
            ),
            81 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q33_reputation_positive',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            82 => 
            array (
              '#text' => '
      ',
            ),
            83 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q34_marketing_effective',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            84 => 
            array (
              '#text' => '
      ',
            ),
            85 => 
            array (
              '#tag' => 'af-field',
              'name' => 'Unified_Self_Assessment_Survey.q35_advocacy_engaged',
              'defn' => 
              array (
                'required' => true,
                'input_attrs' => 
                array (
                ),
              ),
            ),
            86 => 
            array (
              '#text' => '
    ',
            ),
          ),
        ),
        6 => 
        array (
          '#text' => '
  ',
        ),
      ),
    ),
    8 => 
    array (
      '#text' => '
  ',
    ),
    9 => 
    array (
      '#tag' => 'div',
      '#children' => 
      array (
        0 => 
        array (
          '#text' => '
    ',
        ),
        1 => 
        array (
          '#tag' => 'div',
          '#children' => 
          array (
            0 => 
            array (
              '#tag' => 'div',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        ',
                ),
                1 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.mission_clear',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
        ',
                ),
                3 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.vision_inspiring',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                4 => 
                array (
                  '#text' => '
        ',
                ),
                5 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.values_guide',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                6 => 
                array (
                  '#text' => '
        ',
                ),
                7 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.mission_relevant',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                8 => 
                array (
                  '#text' => '
        ',
                ),
                9 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.strategic_alignment',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                10 => 
                array (
                  '#text' => '
        ',
                ),
                11 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.board_effective',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                12 => 
                array (
                  '#text' => '
        ',
                ),
                13 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.roles_clear',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                14 => 
                array (
                  '#text' => '
        ',
                ),
                15 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.policies_current',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                16 => 
                array (
                  '#text' => '
        ',
                ),
                17 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.board_diverse',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                18 => 
                array (
                  '#text' => '
        ',
                ),
                19 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.board_recruitment',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                20 => 
                array (
                  '#text' => '
        ',
                ),
                21 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.financial_stable',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                22 => 
                array (
                  '#text' => '
        ',
                ),
                23 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.budget_process',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                24 => 
                array (
                  '#text' => '
        ',
                ),
                25 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.revenue_diverse',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                26 => 
                array (
                  '#text' => '
        ',
                ),
                27 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.financial_controls',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                28 => 
                array (
                  '#text' => '
        ',
                ),
                29 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.reserves_adequate',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                30 => 
                array (
                  '#text' => '
        ',
                ),
                31 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.programs_effective',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                32 => 
                array (
                  '#text' => '
        ',
                ),
                33 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.data_collection',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                34 => 
                array (
                  '#text' => '
        ',
                ),
                35 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.continuous_improvement',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                36 => 
                array (
                  '#text' => '
        ',
                ),
                37 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.program_innovation',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                38 => 
                array (
                  '#text' => '
        ',
                ),
                39 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.impact_measurement',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                40 => 
                array (
                  '#text' => '
        ',
                ),
                41 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.staff_skilled',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                42 => 
                array (
                  '#text' => '
        ',
                ),
                43 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.professional_development',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                44 => 
                array (
                  '#text' => '
        ',
                ),
                45 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.succession_planning',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                46 => 
                array (
                  '#text' => '
        ',
                ),
                47 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.compensation_competitive',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                48 => 
                array (
                  '#text' => '
        ',
                ),
                49 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.performance_management',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                50 => 
                array (
                  '#text' => '
        ',
                ),
                51 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.communication_open',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                52 => 
                array (
                  '#text' => '
        ',
                ),
                53 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.culture_positive',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                54 => 
                array (
                  '#text' => '
        ',
                ),
                55 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.change_adaptable',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                56 => 
                array (
                  '#text' => '
        ',
                ),
                57 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.collaboration_strong',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                58 => 
                array (
                  '#text' => '
        ',
                ),
                59 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.learning_culture',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                60 => 
                array (
                  '#text' => '
        ',
                ),
                61 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.stakeholder_engaged',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                62 => 
                array (
                  '#text' => '
        ',
                ),
                63 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.partnerships_strong',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                64 => 
                array (
                  '#text' => '
        ',
                ),
                65 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.reputation_positive',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                66 => 
                array (
                  '#text' => '
        ',
                ),
                67 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.marketing_effective',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                68 => 
                array (
                  '#text' => '
        ',
                ),
                69 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Full_Self_Assessment_Survey.advocacy_engaged',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                  ),
                ),
                70 => 
                array (
                  '#text' => '
      ',
                ),
              ),
            ),
          ),
        ),
        2 => 
        array (
          '#text' => '
    ',
        ),
        3 => 
        array (
          '#tag' => 'div',
          'class' => 'af-container af-layout-inline',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
      ',
            ),
            1 => 
            array (
              '#tag' => 'button',
              'class' => 'af-button btn btn-primary',
              'crm-icon' => 'fa-check',
              'ng-click' => 'afform.submit()',
              'ng-if' => 'afform.showSubmitButton',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => 'Submit Survey',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
    ',
            ),
          ),
        ),
        4 => 
        array (
          '#text' => '
  
  ',
        ),
      ),
    ),
    10 => 
    array (
      '#text' => '
',
    ),
  ),
);
}

// Deprecated function - kept for compatibility
function createFormLayout($activityTypeValue, $customGroupName, $surveyFields, $surveyTitle, $surveyDescription) {
    // This function is deprecated - use createSASSFormLayout() or createSASFFormLayout() instead
    if (count($surveyFields) <= 21) {
        return createSASSFormLayout();
    } else {
        return createSASFFormLayout();
    }
}

?>