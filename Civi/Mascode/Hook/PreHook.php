<?php

namespace Civi\Mascode\Hook;

use Civi\Mascode\Util\CodeGenerator;

class PreHook
{
  public static function handle($op, $objectName, $id, $params)
  {
    if ($objectName == 'Case' && in_array($op, ['edit', 'create'])) {
      $caseTypeId = $params['case_type_id'] ?? null;
      if (!$caseTypeId) return;
      $caseType = \Civi\Api4\CaseType::get()
        ->addSelect('name')
        ->addWhere('id', '=', $caseTypeId)
        ->execute()
        ->first()['name'] ?? null;
      // If this is the creation of a Service Request or a Project...
      if (($caseType == 'service_request' || $caseType == 'project') && $op == 'create') {
        // Generate the MAS code
        $masCode = CodeGenerator::generate($caseType);
        // Get the Service Request custom field ID
        if ($caseType == 'service_request') {
        $serviceId = CodeGenerator::getFieldId('mas_parameters', 'sr_code_last');
        } else {
          $serviceId = CodeGenerator::getFieldId('mas_parameters', 'project_code_last');
        }
        // Update the MAS Case Code
        if ($serviceId) {
          $params["custom_{$serviceId}"] = $masCode;
        }
      } else {
        // If this is the update of a Service Request...
        if ($caseType == 'service_request' && $op == 'edit') {
          $caseStatusId = $params['case_status_id'] ?? null;
          if (!$caseStatusId) return;
          $caseStatus = \Civi\Api4\OptionValue::get()
            ->addSelect('name')
            ->addWhere('option_group_id:name', '=', 'case_status')
            ->addWhere('value', '=', $caseStatusId)
            ->execute()
            ->first()['name'] ?? null;
          // If this is the update of a Service Request to a status of "Project Created"
          // Note that for case statuses, the name field is capitalized with spaces
          if ($caseStatus == 'Project Created') {
            // Get the pre-existing case information
            $civiCase = \Civi\Api4\CiviCase::get()
              ->addSelect('*', 'custom.*', 'case_contact.contact_id')
              ->addJoin('CaseContact AS case_contact', 'LEFT')
              ->addWhere('id', '=', $id)
              ->execute()
              ->first();
            // If no related project, create one
            if (!$civiCase['Cases_SR_Projects_.Related_Project_ID']) {
              // Generate a new Project case with the appropriate ID
              self::createProjectCase($civiCase); // Use self:: for static method call
            }
          }
        }
      }
    } else {
      if ($objectName === 'Contact' && in_array($op, ['edit', 'create'])) {
        // If editing the URL (website) field, make sure it starts with http://
        if (!empty($params['url']) && !preg_match('#^https?://#i', $params['url'])) {
          $params['url'] = 'http://' . $params['url'];
        }
      }
    }
  }
  private static function createProjectCase($civiCase)
  {
    $masCode = CodeGenerator::generate('Project');
    $subject = $masCode . ' ' . $civiCase['subject'];
    $nina = 7608;
    $startDate = date('yyyy-mm-dd');
    $practiceArea = $civiCase['Cases_SR_Projects_.Practice_Area'];
    $relatedSR = $civiCase['Cases_SR_Projects_ . MAS_SR_Case_Code'];
    $clientID = $civiCase['case_contact.contact_id'];
    // Logic using API4 to create a 'Project' case
    $civiCase = \Civi\Api4\CiviCase::create(TRUE)
      ->addValue('case_type_id.name', 'project')
      ->addValue('subject', $subject)
      ->addValue('creator_id', $nina)
      ->addValue('start_date', $startDate)
      ->addValue('status_id.name', 'Active')
      ->addValue('Projects.Practice_Area', $practiceArea)
      ->addValue('MAS_Project_Case_Code', $masCode)
      ->addValue('Related_SR_Case_Code', $relatedSR)
      ->addValue(
        'contact_id',
        [
          $clientID,
        ]
      )
      ->execute();
  }
}