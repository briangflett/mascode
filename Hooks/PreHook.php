<?php

namespace Civi\Mascode\Hooks;

use Civi\Mascode\Utils\CodeGenerator;

class PreHook {
  public static function handle($op, $objectName, $id, &$params) {
    if ($objectName !== 'Case' || $op !== 'create') return;

    $caseTypeId = $params['case_type_id'] ?? null;
    if (!$caseTypeId) return;

    $caseType = \CRM_Case_BAO_CaseType::getLabel($caseTypeId);

    // Only proceed if it's a Service Request
    if (stripos($caseType, 'service request') === false) return;

    // Generate the MAS code
    $masCode = CodeGenerator::generate($params);

    // Get the Service Request custom field ID
    $serviceId = CodeGenerator::getFieldId('Service Request: MAS Case Code', 'service_request');

    if ($serviceId) {
      $params["custom_{$serviceId}"] = $masCode;
    }
  }
}