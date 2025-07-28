<?php

/**
 * Script to update far_contact_id in relationships to match case contact IDs
 *
 * This script:
 * 1. Fetches first 5 relationship rows using SearchKit query converted to API4 (Contact ID 2 = Brian Flett)
 * 2. Updates far_contact_id to the value from case contact
 *
 * USAGE:
 *   cv scr scripts/update_relationship_far_contact.php
 */

// Convert SearchKit query to API4 format
$relationships = \Civi\Api4\Relationship::get(false)
  ->addSelect('id', 'contact_id_a.sort_name', 'relationship_type_id:label', 'contact_id_b.sort_name', 'start_date', 'end_date', 'is_active', 'case_id', 'case_id.subject')
  // Join Case
  ->addJoin('Case AS RelationshipCache_Case_case_id_01', 'LEFT', ['case_id', '=', 'RelationshipCache_Case_case_id_01.id'])
  // Join CaseContact through Case, then Contact
  ->addJoin('Contact AS RelationshipCache_Case_case_id_01_Case_CaseContact_Contact_01', 'LEFT', 'CaseContact', ['RelationshipCache_Case_case_id_01.id', '=', 'RelationshipCache_Case_case_id_01_Case_CaseContact_Contact_01.case_id'])
  // Join Contact for contact_id_a
  ->addJoin('Contact AS RelationshipCache_Contact_contact_id_a_01', 'LEFT', ['contact_id_a', '=', 'RelationshipCache_Contact_contact_id_a_01.id'])
  ->addSelect('RelationshipCache_Case_case_id_01_Case_CaseContact_Contact_01.id', 'RelationshipCache_Case_case_id_01_Case_CaseContact_Contact_01.display_name')
  ->addWhere('contact_id_b', '=', 2)
  ->addWhere('RelationshipCache_Contact_contact_id_a_01.contact_type:name', '=', 'Individual')

  ->execute();

echo "Found " . count($relationships) . " relationships to update:\n\n";

foreach ($relationships as $relationship) {
    $relationshipId = $relationship['id'];
    $currentFarContactId = $relationship['contact_id_b.sort_name'];
    $newFarContactId = $relationship['RelationshipCache_Case_case_id_01_Case_CaseContact_Contact_01.id'];
    $newFarContactName = $relationship['RelationshipCache_Case_case_id_01_Case_CaseContact_Contact_01.display_name'];

    echo "Relationship ID: {$relationshipId}\n";
    echo "  Current contact_id_b: {$currentFarContactId}\n";
    echo "  New contact_id_b: {$newFarContactId} ({$newFarContactName})\n";
    echo "  Case: {$relationship['case_id']} - {$relationship['case_id.subject']}\n";

    if ($newFarContactId) {
        // Update the relationship
        $result = \Civi\Api4\Relationship::update(false)
            ->addWhere('id', '=', $relationshipId)
            ->addValue('contact_id_b', $newFarContactId)
            ->execute();

        echo "  ✓ Updated successfully\n";
    } else {
        echo "  ✗ No case contact found - skipping\n";
    }
    echo "\n";
}

echo "Update process completed.\n";
