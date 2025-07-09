<?php

/**
 * MAS RCS Form Deployment Script
 * 
 * This script creates the Request for Consulting Services (RCS) Afform including:
 * - Complete form structure with Organization, Individual, and Case entities
 * - All necessary field configurations and relationships
 * - Environment-specific ID mapping
 * 
 * IMPORTANT: Update the configuration section below for your target environment
 * NOTE: Comment out sections in the form layout that you want to skip deployment
 * 
 * UPDATED: July 8, 2025 - Configuration updated to match current environment
 * TODO: Form layout in createRCSFormLayout() function needs to be updated with current form
 *       Run /tmp/export_for_update.php to get the current layout for replacement
 */

// ============================================================================
// ENVIRONMENT CONFIGURATION - UPDATE THESE VALUES FOR YOUR TARGET ENVIRONMENT
// ============================================================================

$config = [
    // Environment identifier
    'environment' => 'dev', // 'dev' or 'prod'
    
    // Form settings
    'form_name' => 'afformMASRCSForm',
    'server_route' => 'civicrm/mas-rcs-form',
    'redirect_url' => 'https://masdemo.localhost/thank-you/',
    'is_public' => true,
    'form_permissions' => ['*always allow*'],
    
    // Case type mappings (updated for current environment)
    'case_types' => [
        'service_request' => 3,         // Service Request
        'project' => 4,                 // Project
        'housing_support' => 5,         // Housing Support
        'adult_day_care_referral' => 6, // Adult Day Care Referral
    ],
    
    // Location type mappings (updated for current environment)
    'location_types' => [
        'Home' => 1,                    // Home
        'Work' => 2,                    // Work
        'Main' => 3,                    // Main
        'Other' => 4,                   // Other
        'Billing' => 5,                 // Billing
    ],
    
    // Phone type mappings (updated for current environment)
    'phone_types' => [
        'Phone' => 1,                   // Phone
        'Mobile' => 2,                  // Mobile
        'Fax' => 3,                     // Fax
        'Pager' => 4,                   // Pager
        'Voicemail' => 5,               // Voicemail
    ],
    
    // Website type mappings (updated for current environment)
    'website_types' => [
        'Work' => 1,                    // Work
        'Main' => 2,                    // Main
        'Social' => 3,                  // Social
    ],
    
    // Country mappings (find these in your target environment)
    'countries' => [
        'Canada' => 1039,               // Usually consistent across environments
        'United States' => 1228,        // Usually consistent across environments
    ],
    
    // Province mappings (find these in your target environment)
    'provinces' => [
        'Ontario' => 1108,              // Update this ID for your environment
        'Quebec' => 1109,               // Update this ID for your environment
        // Add other provinces as needed
    ],
    
    // Email confirmation template (set to null if not using)
    'email_confirmation_template_id' => 71,  // Template ID confirmed in environment
    
    // Custom field mappings (these should match your environment)
    'custom_fields' => [
        'Organization.Industry' => 'Organization.Industry',
        'Organization.Budget' => 'Organization.Budget',
        'Organization._Employees' => 'Organization._Employees',
        'Organization._Volunteers' => 'Organization._Volunteers',
        'Organization.Charity_Status' => 'Organization.Charity_Status',
        'Organization.Charity_Business_' => 'Organization.Charity_Business_',
        'Organization.Notes' => 'Organization.Notes',
        'Cases_SR_Projects_.Notes' => 'Cases_SR_Projects_.Notes',
        'Cases_SR_Projects_.Virtual_Work' => 'Cases_SR_Projects_.Virtual_Work',
        'Cases_SR_Projects_.Board_Approval' => 'Cases_SR_Projects_.Board_Approval',
        'Cases_SR_Projects_.Requested_Start_Date' => 'Cases_SR_Projects_.Requested_Start_Date',
        'Cases_SR_Projects_.Flexible_Start_Date' => 'Cases_SR_Projects_.Flexible_Start_Date',
        'Cases_SR_Projects_.T_C_Authorized_and_Approved' => 'Cases_SR_Projects_.T_C_Authorized_and_Approved',
        'Cases_SR_Projects_.Authorized_Name' => 'Cases_SR_Projects_.Authorized_Name',
        'Cases_SR_Projects_.Authorized_Title' => 'Cases_SR_Projects_.Authorized_Title',
        'Cases_SR_Projects_.Authorized_Date' => 'Cases_SR_Projects_.Authorized_Date',
    ],
];

// ============================================================================
// RCS FORM DEFINITION - COMMENT OUT SECTIONS YOU WANT TO SKIP
// ============================================================================

$rcsFormDefinition = [
    'name' => $config['form_name'],
    'type' => 'form',
    'title' => 'Request for Consulting Services',
    'description' => 'MAS Request for Consulting Assistance form.',
    'placement' => ['msg_token_single'],
    'icon' => 'fa-list-alt',
    'server_route' => $config['server_route'],
    'is_public' => $config['is_public'],
    'permission' => $config['form_permissions'],
    'permission_operator' => 'AND',
    'redirect' => $config['redirect_url'],
    'submit_enabled' => true,
    'create_submission' => true,
    'manual_processing' => false,
    'allow_verification_by_email' => false,
    'email_confirmation_template_id' => $config['email_confirmation_template_id'],
    'autosave_draft' => false,
];

// ============================================================================
// DEPLOYMENT SCRIPT - DO NOT MODIFY BELOW THIS LINE
// ============================================================================

echo "=== MAS RCS Form Deployment Script ===\n";
echo "Environment: {$config['environment']}\n";
echo "Form: {$config['form_name']}\n";
echo "Starting deployment...\n\n";

$results = [];

try {
    // Step 1: Validate environment
    echo "Step 1: Validating environment...\n";
    validateEnvironment($config);
    echo "✓ Environment validation passed\n\n";
    
    // Step 2: Create form layout
    echo "Step 2: Building form layout...\n";
    $formLayout = createRCSFormLayout($config);
    echo "✓ Form layout created with " . countFormElements($formLayout) . " elements\n\n";
    
    // Step 3: Deploy the Afform
    echo "Step 3: Deploying RCS Afform...\n";
    $result = deployRCSAfform($rcsFormDefinition, $formLayout, $config);
    
    if ($result['action'] === 'created') {
        echo "✓ Created RCS form: {$config['form_name']}\n";
    } elseif ($result['action'] === 'updated') {
        echo "✓ Updated RCS form: {$config['form_name']}\n";
    } else {
        echo "- RCS form already exists and is unchanged\n";
    }
    
    $results = $result;
    
    echo "\n=== DEPLOYMENT COMPLETED SUCCESSFULLY ===\n";
    echo "Summary:\n";
    echo "- Form name: {$config['form_name']}\n";
    echo "- Form route: {$config['server_route']}\n";
    echo "- Action taken: {$result['action']}\n";
    echo "- Redirect URL: {$config['redirect_url']}\n";
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
    // Check if Afform extension is available
    try {
        \Civi\Api4\Afform::get(FALSE)->addSelect('name')->setLimit(1)->execute();
    } catch (Exception $e) {
        throw new Exception('Afform extension is not installed or enabled');
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
    
    // Validate email confirmation template if specified
    if (!empty($config['email_confirmation_template_id'])) {
        $template = \Civi\Api4\MessageTemplate::get(FALSE)
            ->addWhere('id', '=', $config['email_confirmation_template_id'])
            ->execute()->first();
        
        if (!$template) {
            throw new Exception("Email confirmation template with ID {$config['email_confirmation_template_id']} not found");
        }
    }
    
    return true;
}

/**
 * Create the complete RCS form layout
 */
function createRCSFormLayout($config) {
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
        'source' => 'Request for Consulting Services',
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
      'autofill-relationship' => 'Individual3',
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
        'source' => 'Request for Consulting Services',
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
      'autofill' => 'relationship:President of',
      'autofill-relationship' => 'Organization1',
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
        'source' => 'Request for Consulting Services',
      ),
      'type' => 'Individual',
      'name' => 'Individual2',
      'label' => 'Individual 2',
      'actions' => 
      array (
        'create' => true,
        'update' => true,
      ),
      'security' => 'FBAC',
      'autofill' => 'relationship:Executive Director of',
      'autofill-relationship' => 'Organization1',
      'contact-dedupe' => 'Individual.Supervised',
    ),
    6 => 
    array (
      '#text' => '
  ',
    ),
    7 => 
    array (
      '#tag' => 'af-entity',
      'data' => 
      array (
        'source' => 'Request for Consulting Services',
      ),
      'type' => 'Individual',
      'name' => 'Individual3',
      'label' => 'Individual 3',
      'actions' => 
      array (
        'create' => false,
        'update' => true,
      ),
      'security' => 'FBAC',
      'url-autofill' => '0',
      'autofill' => 'entity_id',
      'contact-dedupe' => 'Individual.Supervised',
    ),
    8 => 
    array (
      '#text' => '
  ',
    ),
    9 => 
    array (
      '#tag' => 'af-entity',
      'data' => 
      array (
        'contact_id' => 'Organization1',
        'case_type_id' => 3,
      ),
      'actions' => 
      array (
        'create' => false,
        'update' => true,
      ),
      'type' => 'Case',
      'name' => 'Case1',
      'label' => 'Case 1',
      'security' => 'FBAC',
      'url-autofill' => '0',
      'case-autofill' => 'entity_id',
    ),
    10 => 
    array (
      '#text' => '
  ',
    ),
    11 => 
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
          'af-title' => 'Organization',
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
                3 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'Organization.Industry',
                  'defn' => 
                  array (
                    'label' => 'Industry',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
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
                  'name' => 'Organization.Budget',
                  'defn' => 
                  array (
                    'label' => 'Annual Budget',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
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
                  'name' => 'Organization._Employees',
                  'defn' => 
                  array (
                    'label' => '# Employees',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
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
                  'name' => 'Organization._Volunteers',
                  'defn' => 
                  array (
                    'label' => '# Volunteers',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
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
                  'name' => 'Organization.Charity_Status',
                  'defn' => 
                  array (
                    'required' => true,
                    'input_attrs' => 
                    array (
                    ),
                    'label' => 'Charity Status',
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
                  'name' => 'Organization.Charity_Business_',
                  'defn' => 
                  array (
                    'label' => 'Charity/Business #',
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
                  'name' => 'Organization.Notes',
                  'defn' => 
                  array (
                    'label' => 'Notes',
                  ),
                ),
                16 => 
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
              'af-join' => 'Address',
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
                      'name' => 'street_address',
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
                      'name' => 'city',
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
                      'name' => 'state_province_id',
                      'defn' => 
                      array (
                        'input_type' => 'Select',
                        'input_attrs' => 
                        array (
                        ),
                        'afform_default' => 1108,
                        'label' => 'Province',
                        'required' => true,
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
                      'name' => 'postal_code',
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
                      'name' => 'country_id',
                      'defn' => 
                      array (
                        'afform_default' => '1039',
                        'input_attrs' => 
                        array (
                        ),
                        'input_type' => 'Hidden',
                        'label' => false,
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
                      'name' => 'location_type_id',
                      'defn' => 
                      array (
                        'afform_default' => '1',
                        'input_attrs' => 
                        array (
                        ),
                        'input_type' => 'Hidden',
                        'required' => false,
                        'label' => false,
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
                      'name' => 'is_primary',
                      'defn' => 
                      array (
                        'afform_default' => '1',
                        'input_type' => 'Hidden',
                        'label' => false,
                      ),
                    ),
                    14 => 
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
              '#tag' => 'div',
              'af-join' => 'Phone',
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
                      'name' => 'phone',
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
                      'name' => 'phone_type_id',
                      'defn' => 
                      array (
                        'afform_default' => '1',
                        'input_attrs' => 
                        array (
                        ),
                        'label' => false,
                        'input_type' => 'Hidden',
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
                      'name' => 'is_primary',
                      'defn' => 
                      array (
                        'afform_default' => '1',
                        'label' => false,
                        'input_type' => 'Hidden',
                      ),
                    ),
                    8 => 
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
            7 => 
            array (
              '#tag' => 'div',
              'af-join' => 'Website',
              'actions' => 
              array (
                'update' => true,
                'delete' => true,
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
                          'name' => 'url',
                        ),
                        2 => 
                        array (
                          '#text' => '
            ',
                        ),
                        3 => 
                        array (
                          '#tag' => 'af-field',
                          'name' => 'website_type_id',
                          'defn' => 
                          array (
                            'afform_default' => '2',
                            'input_attrs' => 
                            array (
                            ),
                            'label' => false,
                            'input_type' => 'Hidden',
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
          'af-title' => 'President / Board Chair',
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
                                'required' => false,
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
                7 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'do_not_email',
                  'defn' => 
                  array (
                    'input_type' => 'Select',
                    'input_attrs' => 
                    array (
                    ),
                    'options' => 
                    array (
                      0 => 
                      array (
                        'id' => '1',
                        'label' => 'Do not add to email list',
                      ),
                      1 => 
                      array (
                        'id' => '0',
                        'label' => 'Add to email list',
                      ),
                    ),
                    'label' => false,
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
                  'name' => 'employer_id',
                  'defn' => 
                  array (
                    'afform_default' => 'Organization1',
                    'input_attrs' => 
                    array (
                    ),
                    'label' => false,
                    'input_type' => 'Hidden',
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
                  'name' => 'job_title',
                  'defn' => 
                  array (
                    'input_type' => 'Hidden',
                    'input_attrs' => 
                    array (
                    ),
                    'label' => false,
                    'afform_default' => 'President',
                  ),
                ),
                12 => 
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
          'af-fieldset' => 'Individual2',
          'class' => 'af-container af-container-style-pane',
          'af-title' => 'Executive Director',
          'style' => 'border: 3px solid #619ee6',
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
                      'name' => 'first_name',
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
                                'required' => false,
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
                    6 => 
                    array (
                      '#text' => '
          ',
                    ),
                    7 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'do_not_email',
                      'defn' => 
                      array (
                        'input_type' => 'Select',
                        'input_attrs' => 
                        array (
                        ),
                        'options' => 
                        array (
                          0 => 
                          array (
                            'id' => '1',
                            'label' => 'Do not add to email list',
                          ),
                          1 => 
                          array (
                            'id' => '0',
                            'label' => 'Add to email list',
                          ),
                        ),
                        'label' => false,
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
                      'name' => 'employer_id',
                      'defn' => 
                      array (
                        'input_type' => 'Hidden',
                        'input_attrs' => 
                        array (
                        ),
                        'label' => false,
                        'afform_default' => 'Organization1',
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
                      'name' => 'job_title',
                      'defn' => 
                      array (
                        'input_type' => 'Hidden',
                        'input_attrs' => 
                        array (
                        ),
                        'label' => false,
                        'afform_default' => 'Executive Director',
                      ),
                    ),
                    12 => 
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
        7 => 
        array (
          '#tag' => 'fieldset',
          'af-fieldset' => 'Individual3',
          'class' => 'af-container af-container-style-pane',
          'af-title' => 'Primary Contact for this Request',
          'style' => 'border: 3px solid #619ee6',
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
                6 => 
                array (
                  '#text' => '
        ',
                ),
                7 => 
                array (
                  '#tag' => 'div',
                  'af-join' => 'Phone',
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
                              'name' => 'phone',
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
                              'name' => 'phone_type_id',
                              'defn' => 
                              array (
                                'afform_default' => '1',
                                'input_attrs' => 
                                array (
                                ),
                                'label' => false,
                                'input_type' => 'Hidden',
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
                              'name' => 'is_primary',
                              'defn' => 
                              array (
                                'afform_default' => '1',
                                'label' => false,
                                'input_type' => 'Hidden',
                              ),
                            ),
                            8 => 
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
                8 => 
                array (
                  '#text' => '
        ',
                ),
                9 => 
                array (
                  '#tag' => 'af-field',
                  'name' => 'job_title',
                  'defn' => 
                  array (
                    'label' => 'Role / Job Title',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
                  ),
                ),
                10 => 
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
        8 => 
        array (
          '#text' => '
    ',
        ),
        9 => 
        array (
          '#tag' => 'fieldset',
          'af-fieldset' => 'Case1',
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
              'class' => 'af-container af-layout-inline af-container-style-pane',
              'af-title' => 'Request',
              'style' => 'border: 3px solid #617de6',
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
                  'name' => 'subject',
                  'defn' => 
                  array (
                    'label' => 'Subject',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
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
                  'name' => 'Cases_SR_Projects_.Notes',
                  'defn' => 
                  array (
                    'help_pre' => 'What assistance are you
looking for at this time?  Please
be as specific as you can about the nature of this project  so we can find the right consultant to help
you.',
                    'input_attrs' => 
                    array (
                      'maxlength' => 1000,
                    ),
                    'label' => 'Details',
                    'required' => true,
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
                  'name' => 'Cases_SR_Projects_.Virtual_Work',
                  'defn' => 
                  array (
                    'label' => 'Work Preference',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
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
                  'name' => 'Cases_SR_Projects_.Board_Support',
                  'defn' => 
                  array (
                    'label' => 'Board Support',
                    'required' => true,
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
                  'name' => 'Cases_SR_Projects_.Requested_Start_Date',
                  'defn' => 
                  array (
                    'label' => 'Proposed Start Date',
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
                  'name' => 'Cases_SR_Projects_.Flexible_Start_Date',
                  'defn' => 
                  array (
                    'label' => 'Is Start Date Flexible?',
                  ),
                ),
                12 => 
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
              'class' => 'af-container af-container-style-pane af-layout-inline',
              'af-title' => 'Terms & Conditions',
              'style' => 'border: 3px solid #619ee6',
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
                          '#text' => 'Please review the ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'a',
                          'href' => 'https://masadvise.sharepoint.com/:w:/s/ManagementAdvisoryServiceofOntario/EfvikZuLN_xKix4EDjsxR1gBlvpCcJ1XjF4ZDFMcYF2-Ow?e=INhZvD',
                          'rel' => 'noopener noreferrer',
                          'target' => '_blank',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'MAS Terms & Conditions',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => ' ',
                        ),
                        3 => 
                        array (
                          '#tag' => 'br',
                        ),
                        4 => 
                        array (
                          '#text' => ' and then click this checkbox to confirm ',
                        ),
                        5 => 
                        array (
                          '#tag' => 'br',
                        ),
                        6 => 
                        array (
                          '#text' => ' you are authorized and approve.',
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
                  '#tag' => 'af-field',
                  'name' => 'Cases_SR_Projects_.T_C_Authorized_and_Approved',
                  'defn' => 
                  array (
                    'required' => true,
                    'label' => 'Authorized and Approved',
                    'help_pre' => NULL,
                    'options' => 
                    array (
                      0 => 
                      array (
                        'id' => 'Yes',
                        'label' => 'I have reviewed the terms and conditions and am authorized to approve these conditions on behalf of my agency.',
                      ),
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
                  'name' => 'Cases_SR_Projects_.Authorized_Name',
                  'defn' => 
                  array (
                    'label' => 'Name',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
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
                  'name' => 'Cases_SR_Projects_.Authorized_Title',
                  'defn' => 
                  array (
                    'label' => 'Title',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
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
                  'name' => 'Cases_SR_Projects_.Authorized_Date',
                  'defn' => 
                  array (
                    'label' => 'Date',
                    'input_attrs' => 
                    array (
                    ),
                    'required' => true,
                  ),
                ),
                10 => 
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
              'class' => 'af-container af-container-style-pane',
              'af-title' => 'Donation',
              'style' => 'border: 3px solid #619ee6; background-color: #ffff00',
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
                          '#text' => 'MAS does not charge fees for its services, but we do ask organizations that are financially able to do so to consider giving MAS a donation at the completion of a project. MAS relies on donations to cover its operating expenses.',
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
              '#tag' => 'p',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
        If you have any issues with this form, please email
        ',
                ),
                1 => 
                array (
                  '#tag' => 'a',
                  'href' => 'mailto:info@masadvise.org',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'info@masadvise.org',
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
        12 => 
        array (
          '#text' => '
    ',
        ),
        13 => 
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
              'crm-icon' => 'fa-floppy-disk',
              'ng-click' => 'afform.submitDraft()',
              'ng-if' => 'afform.showSubmitButton',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => 'Save Draft',
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
              '#tag' => 'button',
              'class' => 'af-button btn btn-primary',
              'crm-icon' => 'fa-check',
              'ng-click' => 'afform.submit()',
              'ng-if' => 'afform.showSubmitButton',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => 'Submit',
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
        14 => 
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
  ),
);
}

/**
 * Create organization fieldset
 */
function createOrganizationFieldset($config) {
    return [
        '#tag' => 'fieldset',
        'af-fieldset' => 'Organization1',
        'class' => 'af-container af-container-style-pane',
        'af-title' => 'Organization',
        'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
        '#children' => [
            ['#text' => "\n      "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'organization_name',
                                'defn' => ['required' => true, 'input_attrs' => []]
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => $config['custom_fields']['Organization.Industry'],
                                'defn' => ['label' => 'Industry', 'input_attrs' => [], 'required' => true]
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => $config['custom_fields']['Organization.Budget'],
                                'defn' => ['label' => 'Annual Budget', 'input_attrs' => [], 'required' => true]
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => $config['custom_fields']['Organization._Employees'],
                                'defn' => ['label' => '# Employees', 'input_attrs' => [], 'required' => true]
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => $config['custom_fields']['Organization._Volunteers'],
                                'defn' => ['label' => '# Volunteers', 'input_attrs' => [], 'required' => true]
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => $config['custom_fields']['Organization.Charity_Business_'],
                                'defn' => ['label' => 'Charity/Business #', 'input_attrs' => []]
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => $config['custom_fields']['Organization.Notes'],
                                'defn' => ['label' => 'Notes']
                            ],
                            ['#text' => "\n        "]
                        ]
                    ],
                    ['#text' => "\n      "]
                ]
            ],
            ['#text' => "\n      "],
            
            // Address section
            createAddressSection($config),
            
            ['#text' => "\n      "],
            
            // Phone section
            createPhoneSection($config),
            
            ['#text' => "\n      "],
            
            // Website section
            createWebsiteSection($config),
            
            ['#text' => "\n    "]
        ]
    ];
}

/**
 * Create address section for organization
 */
function createAddressSection($config) {
    return [
        '#tag' => 'div',
        'af-join' => 'Address',
        'actions' => ['update' => true, 'delete' => true],
        'data' => ['is_primary' => true],
        '#children' => [
            ['#text' => "\n        "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n          "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'street_address',
                                'defn' => ['required' => true, 'input_attrs' => []]
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'city',
                                'defn' => ['required' => true, 'input_attrs' => []]
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'state_province_id',
                                'defn' => [
                                    'input_type' => 'Select',
                                    'input_attrs' => [],
                                    'afform_default' => $config['provinces']['Ontario'],
                                    'label' => 'Province',
                                    'required' => true
                                ]
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'postal_code'
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'country_id',
                                'defn' => [
                                    'afform_default' => (string)$config['countries']['Canada'],
                                    'input_attrs' => [],
                                    'input_type' => 'Hidden',
                                    'label' => false
                                ]
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'location_type_id',
                                'defn' => [
                                    'afform_default' => (string)$config['location_types']['Home'],
                                    'input_attrs' => [],
                                    'input_type' => 'Hidden',
                                    'required' => false,
                                    'label' => false
                                ]
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'is_primary',
                                'defn' => [
                                    'afform_default' => '1',
                                    'input_type' => 'Hidden',
                                    'label' => false
                                ]
                            ],
                            ['#text' => "\n          "]
                        ]
                    ],
                    ['#text' => "\n        "]
                ]
            ],
            ['#text' => "\n      "]
        ]
    ];
}

/**
 * Create phone section for organization
 */
function createPhoneSection($config) {
    return [
        '#tag' => 'div',
        'af-join' => 'Phone',
        'actions' => ['update' => true, 'delete' => true],
        'data' => ['is_primary' => true],
        '#children' => [
            ['#text' => "\n        "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n          "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'phone'
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'location_type_id',
                                'defn' => [
                                    'afform_default' => (string)$config['location_types']['Home'],
                                    'input_attrs' => [],
                                    'required' => false,
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'phone_type_id',
                                'defn' => [
                                    'afform_default' => (string)$config['phone_types']['Phone'],
                                    'input_attrs' => [],
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'is_primary',
                                'defn' => [
                                    'afform_default' => '1',
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n          "]
                        ]
                    ],
                    ['#text' => "\n        "]
                ]
            ],
            ['#text' => "\n      "]
        ]
    ];
}

/**
 * Create website section for organization
 */
function createWebsiteSection($config) {
    return [
        '#tag' => 'div',
        'af-join' => 'Website',
        'actions' => ['update' => true, 'delete' => true],
        '#children' => [
            ['#text' => "\n        "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n          "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'url'
                            ],
                            ['#text' => "\n            "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'website_type_id',
                                'defn' => [
                                    'afform_default' => (string)$config['website_types']['Work'],
                                    'input_attrs' => [],
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n          "]
                        ]
                    ],
                    ['#text' => "\n        "]
                ]
            ],
            ['#text' => "\n      "]
        ]
    ];
}

/**
 * Create president fieldset
 */
function createPresidentFieldset($config) {
    return [
        '#tag' => 'fieldset',
        'af-fieldset' => 'Individual1',
        'class' => 'af-container af-container-style-pane',
        'af-title' => 'President / Board Chair',
        'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
        '#children' => [
            ['#text' => "\n      "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'first_name'
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'last_name'
                            ],
                            ['#text' => "\n          "],
                            createEmailSection($config, 'Individual1'),
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'do_not_email',
                                'defn' => [
                                    'input_type' => 'Select',
                                    'input_attrs' => [],
                                    'options' => [
                                        ['id' => '1', 'label' => 'Do not add to email list'],
                                        ['id' => '0', 'label' => 'Add to email list']
                                    ]
                                ]
                            ],
                            ['#text' => "\n          "],
                            createHiddenJobField('President'),
                            ['#text' => "\n        "]
                        ]
                    ],
                    ['#text' => "\n      "]
                ]
            ],
            ['#text' => "\n    "]
        ]
    ];
}

/**
 * Create executive director fieldset
 */
function createExecutiveDirectorFieldset($config) {
    return [
        '#tag' => 'fieldset',
        'af-fieldset' => 'Individual2',
        'class' => 'af-container af-container-style-pane',
        'af-title' => 'Executive Director',
        'style' => 'border: 3px solid #619ee6',
        '#children' => [
            ['#text' => "\n      "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'first_name'
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'last_name'
                            ],
                            ['#text' => "\n          "],
                            createEmailSection($config, 'Individual2'),
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'do_not_email',
                                'defn' => [
                                    'input_type' => 'Select',
                                    'input_attrs' => [],
                                    'options' => [
                                        ['id' => '1', 'label' => 'Do not add to email list'],
                                        ['id' => '0', 'label' => 'Add to email list']
                                    ]
                                ]
                            ],
                            ['#text' => "\n          "],
                            createHiddenJobField('Executive Director'),
                            ['#text' => "\n        "]
                        ]
                    ],
                    ['#text' => "\n      "]
                ]
            ],
            ['#text' => "\n    "]
        ]
    ];
}

/**
 * Create primary contact fieldset
 */
function createPrimaryContactFieldset($config) {
    return [
        '#tag' => 'fieldset',
        'af-fieldset' => 'Individual3',
        'class' => 'af-container af-container-style-pane',
        'af-title' => 'Primary Contact for this Request',
        'style' => 'border: 3px solid #619ee6',
        '#children' => [
            ['#text' => "\n      "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'first_name',
                                'defn' => ['required' => true, 'input_attrs' => []]
                            ],
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'last_name',
                                'defn' => ['required' => true, 'input_attrs' => []]
                            ],
                            ['#text' => "\n          "],
                            createEmailSection($config, 'Individual3'),
                            ['#text' => "\n          "],
                            createPhoneSectionIndividual($config),
                            ['#text' => "\n          "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'job_title',
                                'defn' => [
                                    'label' => 'Role / Job Title',
                                    'input_attrs' => [],
                                    'required' => true
                                ]
                            ],
                            ['#text' => "\n        "]
                        ]
                    ],
                    ['#text' => "\n      "]
                ]
            ],
            ['#text' => "\n    "]
        ]
    ];
}

/**
 * Create request details fieldset
 */
function createRequestFieldset($config) {
    return [
        '#tag' => 'fieldset',
        'af-fieldset' => 'Case1',
        'class' => 'af-container',
        '#children' => [
            ['#text' => "\n      "],
            [
                '#tag' => 'fieldset',
                'class' => 'af-container af-layout-inline af-container-style-pane',
                'af-title' => 'Request',
                'style' => 'border: 3px solid #617de6',
                '#children' => [
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'af-field',
                        'name' => 'subject',
                        'defn' => [
                            'label' => 'Subject',
                            'input_attrs' => [],
                            'required' => true
                        ]
                    ],
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'af-field',
                        'name' => $config['custom_fields']['Cases_SR_Projects_.Notes'],
                        'defn' => [
                            'help_pre' => "What assistance are you\nlooking for at this time?  Please\nbe as specific as you can about the nature of this project  so we can find the right consultant to help\nyou.",
                            'input_attrs' => ['maxlength' => 1000],
                            'label' => 'Details',
                            'required' => true
                        ]
                    ],
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'af-field',
                        'name' => $config['custom_fields']['Cases_SR_Projects_.Virtual_Work'],
                        'defn' => [
                            'label' => 'Work Preference',
                            'input_attrs' => [],
                            'required' => true
                        ]
                    ],
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'af-field',
                        'name' => $config['custom_fields']['Cases_SR_Projects_.Board_Approval'],
                        'defn' => [
                            'required' => true,
                            'label' => 'Board Approval'
                        ]
                    ],
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'af-field',
                        'name' => $config['custom_fields']['Cases_SR_Projects_.Requested_Start_Date'],
                        'defn' => [
                            'label' => 'Proposed Start Date',
                            'input_attrs' => []
                        ]
                    ],
                    ['#text' => "\n        "],
                    [
                        '#tag' => 'af-field',
                        'name' => $config['custom_fields']['Cases_SR_Projects_.Flexible_Start_Date'],
                        'defn' => [
                            'label' => 'Is Start Date Flexible?'
                        ]
                    ],
                    ['#text' => "\n      "]
                ]
            ],
            ['#text' => "\n    "]
        ]
    ];
}

/**
 * Create terms & conditions fieldset
 */
function createTermsConditionsFieldset($config) {
    return [
        '#tag' => 'fieldset',
        'class' => 'af-container af-container-style-pane af-layout-inline',
        'af-title' => 'Terms & Conditions',
        'style' => 'border: 3px solid #619ee6',
        '#children' => [
            ['#text' => "\n        "],
            [
                '#tag' => 'div',
                'class' => 'af-markup',
                '#children' => [
                    ['#text' => "\n          \n          "],
                    [
                        '#tag' => 'p',
                        '#children' => [
                            ['#text' => 'Please review the '],
                            [
                                '#tag' => 'a',
                                'href' => 'https://masadvise.sharepoint.com/:w:/s/ManagementAdvisoryServiceofOntario/EfvikZuLN_xKix4EDjsxR1gBlvpCcJ1XjF4ZDFMcYF2-Ow?e=INhZvD',
                                'rel' => 'noopener noreferrer',
                                'target' => '_blank',
                                '#children' => [
                                    ['#text' => 'MAS Terms & Conditions']
                                ]
                            ],
                            ['#text' => ' and then click this checkbox to confirm you are authorized and approve.']
                        ]
                    ],
                    ['#text' => "\n\n        \n        "]
                ]
            ],
            ['#text' => "\n        "],
            [
                '#tag' => 'af-field',
                'name' => $config['custom_fields']['Cases_SR_Projects_.T_C_Authorized_and_Approved'],
                'defn' => [
                    'required' => true,
                    'label' => 'Authorized and Approved'
                ]
            ],
            ['#text' => "\n        "],
            [
                '#tag' => 'af-field',
                'name' => $config['custom_fields']['Cases_SR_Projects_.Authorized_Name'],
                'defn' => [
                    'label' => 'Name',
                    'input_attrs' => [],
                    'required' => true
                ]
            ],
            ['#text' => "\n        "],
            [
                '#tag' => 'af-field',
                'name' => $config['custom_fields']['Cases_SR_Projects_.Authorized_Title'],
                'defn' => [
                    'label' => 'Title',
                    'input_attrs' => [],
                    'required' => true
                ]
            ],
            ['#text' => "\n        "],
            [
                '#tag' => 'af-field',
                'name' => $config['custom_fields']['Cases_SR_Projects_.Authorized_Date'],
                'defn' => [
                    'label' => 'Date',
                    'input_attrs' => [],
                    'required' => true
                ]
            ],
            ['#text' => "\n      "]
        ]
    ];
}

/**
 * Create donation fieldset
 */
function createDonationFieldset($config) {
    return [
        '#tag' => 'fieldset',
        'class' => 'af-container af-container-style-pane',
        'af-title' => 'Donation',
        'style' => 'border: 3px solid #619ee6; background-color: #ffff00',
        '#children' => [
            ['#text' => "\n        "],
            [
                '#tag' => 'div',
                'class' => 'af-markup',
                '#children' => [
                    ['#text' => "\n          \n          \n          "],
                    [
                        '#tag' => 'p',
                        '#children' => [
                            ['#text' => 'MAS does not charge fees for its services, but we do ask organizations that are financially able to do so to consider giving MAS a donation at the completion of a project. MAS relies on donations to cover its operating expenses.']
                        ]
                    ],
                    ['#text' => "\n\n        \n        \n        "]
                ]
            ],
            ['#text' => "\n      "]
        ]
    ];
}

/**
 * Create support information
 */
function createSupportInfo($config) {
    return [
        '#tag' => 'div',
        'class' => 'af-markup',
        '#children' => [
            ['#text' => "\n      \n      \n      \n      "],
            [
                '#tag' => 'p',
                '#children' => [
                    ['#text' => "\n        If you have any issues with this form, please email\n        "],
                    [
                        '#tag' => 'a',
                        'href' => 'mailto:info@masadvise.org',
                        '#children' => [
                            ['#text' => 'info@masadvise.org']
                        ]
                    ],
                    ['#text' => "\n      "]
                ]
            ],
            ['#text' => "\n    \n    \n    \n    "]
        ]
    ];
}

/**
 * Create submit buttons
 */
function createSubmitButtons($config) {
    return [
        '#tag' => 'div',
        'class' => 'af-container af-layout-inline',
        '#children' => [
            ['#text' => "\n      "],
            [
                '#tag' => 'button',
                'class' => 'af-button btn btn-primary',
                'crm-icon' => 'fa-floppy-disk',
                'ng-click' => 'afform.submitDraft()',
                'ng-if' => 'afform.showSubmitButton',
                '#children' => [
                    ['#text' => 'Save Draft']
                ]
            ],
            ['#text' => "\n      "],
            [
                '#tag' => 'button',
                'class' => 'af-button btn btn-primary',
                'crm-icon' => 'fa-check',
                'ng-click' => 'afform.submit()',
                'ng-if' => 'afform.showSubmitButton',
                '#children' => [
                    ['#text' => 'Submit']
                ]
            ],
            ['#text' => "\n    "]
        ]
    ];
}

/**
 * Create email section for individuals
 */
function createEmailSection($config, $entityName) {
    return [
        '#tag' => 'div',
        'af-join' => 'Email',
        'actions' => ['update' => true, 'delete' => true],
        'data' => ['is_primary' => true],
        '#children' => [
            ['#text' => "\n            "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n              "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n                "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'email',
                                'defn' => $entityName === 'Individual3' ? 
                                    ['required' => true, 'input_attrs' => []] :
                                    ['required' => false, 'input_attrs' => []]
                            ],
                            ['#text' => "\n                "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'location_type_id',
                                'defn' => [
                                    'afform_default' => '1',
                                    'input_attrs' => [],
                                    'required' => false,
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n                "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'is_primary',
                                'defn' => [
                                    'afform_default' => '1',
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n              "]
                        ]
                    ],
                    ['#text' => "\n            "]
                ]
            ],
            ['#text' => "\n          "]
        ]
    ];
}

/**
 * Create phone section for primary contact
 */
function createPhoneSectionIndividual($config) {
    return [
        '#tag' => 'div',
        'af-join' => 'Phone',
        'actions' => ['update' => true, 'delete' => true],
        'data' => ['is_primary' => true],
        '#children' => [
            ['#text' => "\n            "],
            [
                '#tag' => 'div',
                'actions' => ['update' => true, 'delete' => true],
                'class' => 'af-container',
                '#children' => [
                    ['#text' => "\n              "],
                    [
                        '#tag' => 'div',
                        'class' => 'af-container af-layout-inline',
                        '#children' => [
                            ['#text' => "\n                "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'phone'
                            ],
                            ['#text' => "\n                "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'location_type_id',
                                'defn' => [
                                    'afform_default' => '1',
                                    'input_attrs' => [],
                                    'required' => false,
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n                "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'phone_type_id',
                                'defn' => [
                                    'afform_default' => '1',
                                    'input_attrs' => [],
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n                "],
                            [
                                '#tag' => 'af-field',
                                'name' => 'is_primary',
                                'defn' => [
                                    'afform_default' => '1',
                                    'label' => false,
                                    'input_type' => 'Hidden'
                                ]
                            ],
                            ['#text' => "\n              "]
                        ]
                    ],
                    ['#text' => "\n            "]
                ]
            ],
            ['#text' => "\n          "]
        ]
    ];
}

/**
 * Create hidden job title fields
 */
function createHiddenJobField($jobTitle) {
    return [
        '#tag' => 'af-field',
        'name' => 'job_title',
        'defn' => [
            'input_type' => 'Hidden',
            'input_attrs' => [],
            'label' => false,
            'afform_default' => $jobTitle
        ]
    ];
}

/**
 * Deploy the RCS Afform
 */
function deployRCSAfform($formDefinition, $formLayout, $config) {
    // Check if form already exists
    $existingForm = \Civi\Api4\Afform::get(FALSE)
        ->addWhere('name', '=', $config['form_name'])
        ->execute()->first();
    
    // Complete form data
    $formData = $formDefinition;
    $formData['layout'] = [$formLayout];
    
    if ($existingForm) {
        // Update existing form
        $result = \Civi\Api4\Afform::update(FALSE)
            ->addWhere('name', '=', $config['form_name'])
            ->setValues($formData)
            ->execute()->first();
        $action = 'updated';
    } else {
        // Create new form
        $result = \Civi\Api4\Afform::create(FALSE)
            ->setValues($formData)
            ->execute()->first();
        $action = 'created';
    }
    
    return [
        'action' => $action,
        'form_data' => $result,
    ];
}

/**
 * Count form elements for reporting
 */
function countFormElements($layout, $count = 0) {
    if (is_array($layout)) {
        foreach ($layout as $element) {
            if (is_array($element)) {
                if (isset($element['#tag'])) {
                    $count++;
                }
                if (isset($element['#children'])) {
                    $count = countFormElements($element['#children'], $count);
                }
            }
        }
    }
    return $count;
}

?>