<?php

// Usage: cv scr scripts/mas_code_2php

use Civi\Mascode\Util\CodeGenerator;

$cases = \Civi\Api4\CiviCase::get(false)
  ->addSelect('id', 'subject', 'case_type_id:name')
  ->addWhere('start_date', '>', '2025-02-13')
  ->execute();


foreach ($cases as $case) {
    if ($case['case_type_id:name'] === 'service_request') {
        $masSrCaseCode = CodeGenerator::generate('service_request');
        $result = \Civi\Api4\CiviCase::update(false)
            ->addValue('Cases_SR_Projects_.MAS_SR_Case_Code', $masSrCaseCode)
            ->addWhere('id', '=', $case['id'])
            ->execute();
    } elseif ($case['case_type_id:name'] === 'project') {
        $masPCaseCode = CodeGenerator::generate('project');
        $result = \Civi\Api4\CiviCase::update(false)
            ->addValue('Projects.MAS_Project_Case_Code', $masPCaseCode)
            ->addWhere('id', '=', $case['id'])
            ->execute();
    }
}
