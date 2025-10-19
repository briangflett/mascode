<?php

namespace Civi\Mascode\Service;

/**
 * Service for moving cases between organizations
 *
 * Handles the business logic for transferring cases, activities, and relationships
 * from one organization to another.
 */
class CaseMoveService {

  /**
   * Move cases between two organizations
   *
   * @param int $fromOrgId The source organization ID
   * @param int $toOrgId The destination organization ID
   * @return array Result information including cases moved, messages, and errors
   * @throws \CRM_Core_Exception
   */
  public function moveCases($fromOrgId, $toOrgId) {
    // Validate inputs
    if ($fromOrgId === $toOrgId) {
      throw new \CRM_Core_Exception('Source and destination organizations must be different.');
    }

    // Verify both contacts are organizations
    $contacts = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'contact_type', 'display_name')
      ->addWhere('id', 'IN', [$fromOrgId, $toOrgId])
      ->execute()
      ->indexBy('id');

    foreach ([$fromOrgId, $toOrgId] as $contactId) {
      if (!isset($contacts[$contactId]) || $contacts[$contactId]['contact_type'] !== 'Organization') {
        throw new \CRM_Core_Exception('Both contacts must be Organizations.');
      }
    }

    $fromContact = $contacts[$fromOrgId];
    $toContact = $contacts[$toOrgId];

    // Find cases to move from source organization
    $casesToMove = $this->findCasesForContact($fromOrgId);

    if (empty($casesToMove)) {
      return [
        'message' => "No cases found to move from {$fromContact['display_name']}.",
        'cases_moved' => 0,
        'from_contact' => $fromContact['display_name'],
        'to_contact' => $toContact['display_name'],
      ];
    }

    // Move the cases
    $casesMoved = 0;
    $errors = [];

    foreach ($casesToMove as $caseId) {
      try {
        $this->moveSingleCase($caseId, $fromOrgId, $toOrgId);
        $casesMoved++;
        \Civi::log()->info("MASCode: Moved case {$caseId} from {$fromOrgId} to {$toOrgId}");
      } catch (\Exception $e) {
        $errors[] = "Case {$caseId}: " . $e->getMessage();
        \Civi::log()->error("MASCode: Error moving case {$caseId}: " . $e->getMessage());
      }
    }

    return [
      'message' => "Successfully moved {$casesMoved} case(s) from {$fromContact['display_name']} to {$toContact['display_name']}.",
      'cases_moved' => $casesMoved,
      'total_cases_found' => count($casesToMove),
      'from_contact' => $fromContact['display_name'],
      'to_contact' => $toContact['display_name'],
      'errors' => $errors,
    ];
  }

  /**
   * Find all cases for a contact.
   *
   * @param int $contactId
   * @return array Array of case IDs
   */
  private function findCasesForContact($contactId) {
    $sql = "
      SELECT DISTINCT c.id as case_id
      FROM civicrm_case c
      INNER JOIN civicrm_case_contact cc ON cc.case_id = c.id
      WHERE cc.contact_id = %1 AND c.is_deleted = 0
    ";

    $dao = \CRM_Core_DAO::executeQuery($sql, [1 => [$contactId, 'Integer']]);
    $caseIds = [];

    while ($dao->fetch()) {
      $caseIds[] = $dao->case_id;
    }

    return $caseIds;
  }

  /**
   * Move a single case from source to destination contact.
   *
   * @param int $caseId
   * @param int $sourceContactId
   * @param int $destContactId
   */
  private function moveSingleCase($caseId, $sourceContactId, $destContactId) {
    // Update case contacts
    $this->moveCaseContacts($caseId, $sourceContactId, $destContactId);

    // Update activity contacts for case activities
    $this->moveCaseActivityContacts($caseId, $sourceContactId, $destContactId);

    // Update case relationships, preserving protected relationships
    $this->preserveCaseRelationships($caseId, $sourceContactId, $destContactId);
  }

  /**
   * Move case contacts from source to destination.
   *
   * @param int $caseId
   * @param int $sourceContactId
   * @param int $destContactId
   */
  private function moveCaseContacts($caseId, $sourceContactId, $destContactId) {
    $sql = "UPDATE civicrm_case_contact SET contact_id = %1 WHERE case_id = %2 AND contact_id = %3";
    \CRM_Core_DAO::executeQuery($sql, [
      1 => [$destContactId, 'Integer'],
      2 => [$caseId, 'Integer'],
      3 => [$sourceContactId, 'Integer']
    ]);
  }

  /**
   * Move activity contacts for case activities.
   *
   * @param int $caseId
   * @param int $sourceContactId
   * @param int $destContactId
   */
  private function moveCaseActivityContacts($caseId, $sourceContactId, $destContactId) {
    $sql = "
      SELECT a.id
      FROM civicrm_activity a
      INNER JOIN civicrm_case_activity ca ON ca.activity_id = a.id
      WHERE ca.case_id = %1 AND a.is_deleted = 0
    ";

    $dao = \CRM_Core_DAO::executeQuery($sql, [1 => [$caseId, 'Integer']]);

    while ($dao->fetch()) {
      $updateSql = "UPDATE civicrm_activity_contact SET contact_id = %1 WHERE activity_id = %2 AND contact_id = %3";
      \CRM_Core_DAO::executeQuery($updateSql, [
        1 => [$destContactId, 'Integer'],
        2 => [$dao->id, 'Integer'],
        3 => [$sourceContactId, 'Integer']
      ]);
    }
  }

  /**
   * Update case relationships, including Case Client Rep and Case Coordinator relationships.
   *
   * @param int $caseId
   * @param int $sourceContactId
   * @param int $destContactId
   */
  private function preserveCaseRelationships($caseId, $sourceContactId, $destContactId) {
    $sql = "
      SELECT r.id, r.contact_id_a, r.contact_id_b, r.relationship_type_id, rt.name_a_b, rt.name_b_a
      FROM civicrm_relationship r
      INNER JOIN civicrm_relationship_type rt ON r.relationship_type_id = rt.id
      WHERE r.case_id = %1 AND r.is_active = 1
    ";

    $dao = \CRM_Core_DAO::executeQuery($sql, [1 => [$caseId, 'Integer']]);

    while ($dao->fetch()) {
      if ($dao->contact_id_a == $sourceContactId || $dao->contact_id_b == $sourceContactId) {

        // Check if this is a Case Client Rep or Case Coordinator relationship
        $nameAB = strtolower($dao->name_a_b ?? '');
        $nameBA = strtolower($dao->name_b_a ?? '');

        $isCaseRelationship = (strpos($nameAB, 'case client rep') !== false || strpos($nameBA, 'case client rep') !== false ||
                              strpos($nameAB, 'case coordinator') !== false || strpos($nameBA, 'case coordinator') !== false);

        if ($isCaseRelationship) {
          // For Case Client Rep and Case Coordinator, update the organization side
          // but preserve the individual contact side
          if ($dao->contact_id_a == $sourceContactId) {
            $updateSql = "UPDATE civicrm_relationship SET contact_id_a = %1 WHERE id = %2";
            \CRM_Core_DAO::executeQuery($updateSql, [1 => [$destContactId, 'Integer'], 2 => [$dao->id, 'Integer']]);
            \Civi::log()->info("MASCode: UPDATED case relationship {$dao->id} ({$dao->name_a_b}) contact_id_a from {$sourceContactId} to {$destContactId}");
          } elseif ($dao->contact_id_b == $sourceContactId) {
            $updateSql = "UPDATE civicrm_relationship SET contact_id_b = %1 WHERE id = %2";
            \CRM_Core_DAO::executeQuery($updateSql, [1 => [$destContactId, 'Integer'], 2 => [$dao->id, 'Integer']]);
            \Civi::log()->info("MASCode: UPDATED case relationship {$dao->id} ({$dao->name_b_a}) contact_id_b from {$sourceContactId} to {$destContactId}");
          }
        } else {
          // Update other case relationships normally
          if ($dao->contact_id_a == $sourceContactId) {
            $updateSql = "UPDATE civicrm_relationship SET contact_id_a = %1 WHERE id = %2";
            \CRM_Core_DAO::executeQuery($updateSql, [1 => [$destContactId, 'Integer'], 2 => [$dao->id, 'Integer']]);
          } elseif ($dao->contact_id_b == $sourceContactId) {
            $updateSql = "UPDATE civicrm_relationship SET contact_id_b = %1 WHERE id = %2";
            \CRM_Core_DAO::executeQuery($updateSql, [1 => [$destContactId, 'Integer'], 2 => [$dao->id, 'Integer']]);
          }
          \Civi::log()->info("MASCode: UPDATED non-case relationship {$dao->id} from contact {$sourceContactId} to {$destContactId}");
        }
      }
    }
  }

}
