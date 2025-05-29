<?php

// Usage: cv scr scripts/mas_code_1.php

$cases = \Civi\Api4\CiviCase::get(false)
  ->addSelect('id', 'subject', 'case_type_id:name')
  ->addWhere('start_date', '>=', '2020-01-01')
  ->addWhere('start_date', '<=', '2025-02-13')
  ->execute();


foreach ($cases as $case) {
    if ($case['case_type_id:name'] === 'service_request') {
        $masSrCaseCode = "R" . substr($case['subject'], 1, 5);
        $result = \Civi\Api4\CiviCase::update(false)
            ->addValue('Cases_SR_Projects_.MAS_SR_Case_Code', $masSrCaseCode)
            ->addWhere('id', '=', $case['id'])
            ->execute();
    } elseif ($case['case_type_id:name'] === 'project') {
        $masPCaseCode = "P" . substr($case['subject'], 0, 5);
        $result = \Civi\Api4\CiviCase::update(false)
            ->addValue('Projects.MAS_Project_Case_Code', $masPCaseCode)
            ->addWhere('id', '=', $case['id'])
            ->execute();
    }
}
