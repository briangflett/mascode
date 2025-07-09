<?php

/**
 * SASS Custom Fields Deployment Script
 *
 * This script creates the 21 custom fields for afformMASSASS:
 * - 21 Custom Fields (q01_board_established through q21_communication_guidelines)
 *
 * Prerequisites (must exist before running):
 * - Custom Group: Short Self Assessment Survey (extends Activity)
 * - Option Group: Yes/No
 *
 * IMPORTANT: Update the configuration section below for your target environment
 */

// ============================================================================
// ENVIRONMENT CONFIGURATION - UPDATE THESE VALUES FOR YOUR TARGET ENVIRONMENT
// ============================================================================

$config = [
    // Environment identifier
    'environment' => 'prod', // 'dev' or 'prod'

    // Required IDs (must exist in target environment)
    'custom_group_id' => 8,        // ID of "Short Self Assessment Survey" custom group
    'option_group_id' => 119,       // ID of "Yes/No" option group

    // Optional: Custom group name for verification
    'custom_group_name' => 'Short_Self_Assessment_Survey',
    'option_group_name' => 'yes_no',
];

// Custom field definitions (21 questions for Short Assessment)
$customFields = [
    'q01_board_established' => '1. A Board of Directors has been established with at least three individuals on it. The members are not related to each other.',
    'q02_members_elect' => '2. The organization has "Members" who elect the Board of Directors.',
    'q03_board_committees' => '3. The Board establishes and utilizes the help of committees when needed.',
    'q04_legal_compliance' => '4. The Board has reviewed the legal requirements for not-for-profit organizations (Ontario Not-for-profit Corporations Act for provincially registered, CRA/Income Tax Act for federally registered) and are in compliance or working toward it.',
    'q05_governance_documents' => '5. Board governance documents and policies are in place and kept on record. This includes By-laws, Articles (Letters Patent), Committee structures/roles/terms of reference, meeting Minutes (record of decisions), conflict of interest.',
    'q06_mission_vision' => '6. There are clear and succinct Vision, Mission (Mandate) statements that accurately describe what your organization strives for and does, or plans to do.',
    'q07_unique_services' => '7. There is no other organization, in your community, that offers the same services.',
    'q08_strategic_plan' => '8. There is a set of up to 6 strategic goals (Strategic Plan) updated no more than four years ago.',
    'q09_annual_plan' => '9. There is an annual plan of activities, based on/reflecting the strategic goals (Strategic Plan) of the organization. The plan includes both an identification of who is doing (responsible for) what, as well as measurable goals. The Board reviews the progress on this plan at least twice per year.',
    'q10_board_effectiveness' => '10. The Board works effectively, makes timely decisions, and positively supports the goals of the organization.',
    'q11_financial_records' => '11. There is a record of all income and expenditures, and these are regularly reviewed and \'accepted\' by the Board of Directors.',
    'q12_bank_account' => '12. There is a separate bank account for the organization and bank statements are reviewed at least quarterly by the Board of Directors.',
    'q13_budget_planning' => '13. The annual plan of activities includes budget information if money is going to be raised or spent.',
    'q14_financial_reporting' => '14. Where relevant any financial reporting to CRA (Canada Revenue Agency) or any other funders is done on-time, accurately, and is reviewed and approved by the Board.',
    'q15_donation_management' => '15. If and when donations are received, they are recorded, and all donors receive appropriate acknowledgement.',
    'q16_volunteer_involvement' => '16. Volunteers are involved in the organization apart from Board members.',
    'q17_volunteer_selection' => '17. Volunteers are interviewed and selected for suitability for the work they will be doing (even if very briefly) and a record kept confirming this process with their name, contact information and what they will be doing.',
    'q18_volunteer_screening' => '18. If a volunteer will have access to money or vulnerable people (seniors, children) they are appropriately screened.',
    'q19_volunteer_guidelines' => '19. Every volunteer receives a written outline of the tasks they will be taking on, the time commitment/term and who is \'in charge\' (for questions and guidance).',
    'q20_client_involvement' => '20. Individuals representing the client group served by the organization have the opportunity to contribute and/or be a volunteer.',
    'q21_communication_guidelines' => '21. There are guidelines on who may speak for the organization as well as for use of social media to share information.',
];

// ============================================================================
// DEPLOYMENT SCRIPT - DO NOT MODIFY BELOW THIS LINE
// ============================================================================

echo "=== SASS Custom Fields Deployment Script ===\n";
echo "Environment: {$config['environment']}\n";
echo "Starting deployment...\n\n";

$results = [];

try {
    // Step 1: Verify Prerequisites
    echo "Step 1: Verifying Prerequisites...\n";

    // Verify Custom Group exists
    $customGroup = \Civi\Api4\CustomGroup::get(false)
        ->addWhere('id', '=', $config['custom_group_id'])
        ->execute()->first();

    if (!$customGroup) {
        throw new Exception("Custom Group with ID {$config['custom_group_id']} not found. Please create it first.");
    }

    // Verify name matches if provided
    if (!empty($config['custom_group_name']) && $customGroup['name'] !== $config['custom_group_name']) {
        echo "WARNING: Custom Group name mismatch. Expected '{$config['custom_group_name']}', found '{$customGroup['name']}'\n";
    }

    echo "✓ Custom Group verified: {$customGroup['title']} (ID: {$customGroup['id']})\n";

    // Verify Option Group exists
    $optionGroup = \Civi\Api4\OptionGroup::get(false)
        ->addWhere('id', '=', $config['option_group_id'])
        ->execute()->first();

    if (!$optionGroup) {
        throw new Exception("Option Group with ID {$config['option_group_id']} not found. Please create it first.");
    }

    // Verify name matches if provided
    if (!empty($config['option_group_name']) && $optionGroup['name'] !== $config['option_group_name']) {
        echo "WARNING: Option Group name mismatch. Expected '{$config['option_group_name']}', found '{$optionGroup['name']}'\n";
    }

    echo "✓ Option Group verified: {$optionGroup['title']} (ID: {$optionGroup['id']})\n";

    // Step 2: Create Custom Fields
    echo "\nStep 2: Creating Custom Fields...\n";

    $weight = 1;
    $createdFields = 0;
    $existingFields = 0;

    foreach ($customFields as $fieldName => $fieldLabel) {
        $existingField = \Civi\Api4\CustomField::get(false)
            ->addWhere('custom_group_id', '=', $config['custom_group_id'])
            ->addWhere('name', '=', $fieldName)
            ->execute()->first();

        if (!$existingField) {
            $customField = \Civi\Api4\CustomField::create(false)
                ->addValue('custom_group_id', $config['custom_group_id'])
                ->addValue('name', $fieldName)
                ->addValue('label', $fieldLabel)
                ->addValue('data_type', 'String')
                ->addValue('html_type', 'Radio')
                ->addValue('option_group_id', $config['option_group_id'])
                ->addValue('weight', $weight)
                ->addValue('is_required', false)
                ->addValue('is_searchable', true)
                ->addValue('is_active', true)
                ->addValue('is_view', false)
                ->execute()->first();
            $createdFields++;
            echo "✓ Created field: $fieldName\n";
        } else {
            $existingFields++;
            echo "  - Field already exists: $fieldName\n";
        }
        $weight++;
    }

    echo "✓ Created $createdFields new fields, $existingFields already existed\n";
    $results['created_fields'] = $createdFields;
    $results['existing_fields'] = $existingFields;

    // Step 3: Clear cache
    echo "\nStep 3: Clearing cache...\n";
    \Civi\Api4\System::flush(false)->execute();
    echo "✓ Cache cleared\n";

    echo "\n=== Deployment Complete ===\n";
    echo "Summary:\n";
    echo "- Custom Group ID: {$config['custom_group_id']}\n";
    echo "- Option Group ID: {$config['option_group_id']}\n";
    echo "- Custom Fields: {$results['created_fields']} created, {$results['existing_fields']} existing\n";

    echo "\nNext Steps:\n";
    echo "1. The afformMASSASS form should now work with these custom fields\n";
    echo "2. Ensure afform files are in /ang directory for version control\n";
    echo "3. Update redirect URL in afform files for production deployment\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
