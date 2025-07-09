<?php

/**
 * SASF Custom Fields Deployment Script
 *
 * This script creates the 35 custom fields for afformMASSASF:
 * - 35 Custom Fields (q01_vision_mission_clear through q35_positive_reputation)
 *
 * Prerequisites (must exist before running):
 * - Custom Group: Full Self Assessment Survey (extends Activity)
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
    'custom_group_id' => 7,        // ID of "Full Self Assessment Survey" custom group
    'option_group_id' => 119,       // ID of "Yes/No" option group

    // Optional: Custom group name for verification
    'custom_group_name' => 'Full_Self_Assessment_Survey',
    'option_group_name' => 'yes_no',
];

// Custom field definitions (35 questions for Full Assessment)
$customFields = [
    'q01_vision_mission_clear' => '1. There are clear and succinct Vision, Mission (Mandate) statements that accurately describe what your organization does and strives for.',
    'q02_unique_services' => '2. There is no other organization, in your community, that offers the same services.',
    'q03_strategic_goals' => '3. There is a set of up to 6 strategic goals (Strategic Plan) updated no more than four years ago',
    'q04_annual_operational_plan' => '4. Staff develop an annual operational plan, based on/reflecting the strategic goals (Strategic Plan) of the organization. The plan includes clear measurable goals and tracks activities/outputs and if possible, impacts which are reported on at least semi-annually.',
    'q05_governance_documents' => '5. Board governance documents and policies are up to date and reviewed on a regular basis. This includes by-laws, Articles (Letters Patent), Committee structures/roles/terms of reference, meeting Minutes (record of decisions), conflict of interest.',
    'q06_board_composition' => '6. The Board of Directors has the appropriate size, composition (including diverse representation) and skill sets to support the organization.',
    'q07_board_committees' => '7. The Board makes use of committees where they are helpful and does so effectively.',
    'q08_board_effectiveness' => '8. The Board works effectively, makes timely decisions, and positively supports the organization.',
    'q09_board_self_assessment' => '9. The Board periodically carries out a self-assessment (at least every 3 years)',
    'q10_budget_financial_statements' => '10. There is an annual budget (balanced) and at least quarterly financial statements based on it, and these are reviewed and \'accepted\' by the Board of Directors or Board Committee.',
    'q11_risk_management' => '11. There is a risk management (financial, reputational, operational) analysis and plan in place updated every two to three years.',
    'q12_contingency_fund' => '12. The organization has at least a three-month operating costs contingency fund set aside.',
    'q13_audit_review' => '13. An annual audit or review engagement is carried out with Board oversight.',
    'q14_funding_contracts' => '14. All funding contracts, donor receipting, and relational follow up with funders, are in good order and the Board is kept updated on these.',
    'q15_donations_policy' => '15. The Board has established a donations policy and/or there is a donor management strategy in place.',
    'q16_financial_reporting' => '16. Financial reporting to CRA (Canada Revenue Agency) and other funders is done on-time, accurately, and is reviewed and approved by the Board/Finance Committee.',
    'q17_financial_viability' => '17. The Board, senior staff, and where applicable Members are comfortable with the financial viability of the organization.',
    'q18_executive_director_confidence' => '18. The Executive Director/most senior staff person has the full confidence of the Board of Directors, and they work well together.',
    'q19_executive_limitations' => '19. The Board has set "executive limitations" and/or supports the operational autonomy of the executive staff.',
    'q20_sufficient_qualified_staff' => '20. There are a sufficient number of appropriately qualified employees (and volunteers) to achieve the mission of the organization.',
    'q21_diverse_employee_cohort' => '21. The employee cohort is diverse and representative of the wider community.',
    'q22_job_descriptions_evaluations' => '22. All employees have a job description (regularly reviewed and updated), an annualized work plan, and an annual evaluation, kept on file.',
    'q23_hr_policy_manual' => '23. There is a current Human Resources Policy/Manual outlining (at a minimum) hiring protocols, terms of employment that comply with any relevant legislation, as well as complaint, conflict of interest, DEI policy, and abuse/harassment policies.',
    'q24_compensation_review' => '24. Employee compensation is reviewed on a regular basis and is in line with that of other similar organizations (including Executive Director).',
    'q25_volunteer_involvement' => '25. Volunteers are involved at the operational and governance levels of the organization.',
    'q26_volunteer_job_descriptions' => '26. Every volunteer has a job description, an outline of their time commitment and any provided benefits, an identified supervisor, and they receive appropriate orientation and training aligned with their role. NOTE this may be as brief as a single paragraph.',
    'q27_volunteer_screening' => '27. The organization has a screening policy and appropriately screens any volunteer with access to money or vulnerable people, including the requirement of a Police Records Check. NOTE – PRC is not required for every volunteer position, only those considered high risk.',
    'q28_diverse_volunteer_cohort' => '28. The volunteer cohort is diverse and representative of the wider community.',
    'q29_client_group_volunteers' => '29. Individuals representing the client group served by the organization have the opportunity to contribute and/or be a volunteer.',
    'q30_volunteer_positions_effective' => '30. Volunteer positions are designed and allocated in the organization effectively and volunteers contribute to achieving the goals of the organization.',
    'q31_fundraising_strategy' => '31. The organization has an effective fundraising strategy, aligned with the annual Budget and related to the Mission.',
    'q32_compelling_communications' => '32. The organization engages in compelling communications (marketing) with clients, donors, funders and/or the public, which aligns with the Vision and Mission.',
    'q33_communication_guidelines' => '33. There are guidelines on who may speak for the organization as well as for use of social media to share information.',
    'q34_website_technology' => '34. There is a good website, and the organization utilizes technology effectively within the context of the Mission and resources available.',
    'q35_positive_reputation' => '35. The organization has a good and positive reputation and is known for doing great work.',
];

// ============================================================================
// DEPLOYMENT SCRIPT - DO NOT MODIFY BELOW THIS LINE
// ============================================================================

echo "=== SASF Custom Fields Deployment Script ===\n";
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
                ->addValue('is_view', true)
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
    echo "1. The afformMASSASF form should now work with these custom fields\n";
    echo "2. Ensure afform files are in /ang directory for version control\n";
    echo "3. Update redirect URL in afform files for production deployment\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
