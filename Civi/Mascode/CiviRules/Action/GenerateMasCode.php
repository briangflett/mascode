<?php

// file: Civi/Mascode/CiviRules/Action/GenerateMasCode.php

namespace Civi\Mascode\CiviRules\Action;

use Civi\Mascode\Util\CodeGenerator;
use Civi\Mascode\Hook\CaseMergeHook;

class GenerateMasCode extends \CRM_Civirules_Action
{
    /**
     * Method to execute the action
     *
     * @param \CRM_Civirules_TriggerData_TriggerData $triggerData
     * @access public
     */
    public function processAction(\CRM_Civirules_TriggerData_TriggerData $triggerData)
    {
        $case = $triggerData->getEntityData('Case');
        // $actionParameters = $this->getActionParameters();

        $caseId = $case['id'];
        $caseTypeId = $case['case_type_id'];

        // Get case type
        $caseType = \Civi\Api4\CaseType::get(false)
            ->addSelect('name')
            ->addWhere('id', '=', $caseTypeId)
            ->execute()
            ->first()['name'] ?? null;

        // Check if this case was created during a contact merge and has preserved codes
        $preservedCodes = $this->getPreservedCodesForCase($caseId);
        
        // Generate MAS SR codes for all new service requests
        if ($caseType == 'service_request') {
            $masSrCaseCode = \Civi\Api4\CiviCase::get(false)
            ->addSelect('Cases_SR_Projects_.MAS_SR_Case_Code')
            ->addWhere('id', '=', $caseId)
            ->execute()
            ->first()['Cases_SR_Projects_.MAS_SR_Case_Code'] ?? null;
            ;

            // If the MAS SR code is empty, check for preserved code first
            if (empty($masSrCaseCode)) {
                if (!empty($preservedCodes['MAS_SR_Case_Code'])) {
                    // Use preserved code from merge
                    $masCode = $preservedCodes['MAS_SR_Case_Code'];
                    \Civi::log()->info("MASCode: Using preserved SR case code {$masCode} for case {$caseId}");
                } else {
                    // Generate the MAS code
                    $masCode = CodeGenerator::generate($caseType);
                }

                // Update the case with the MAS Code
                $result = \Civi\Api4\CiviCase::update(false)
                    ->addValue('Cases_SR_Projects_.MAS_SR_Case_Code', $masCode)
                    ->addWhere('id', '=', $caseId)
                    ->execute();
            }
        } else {
            if ($caseType ==  'project') {
                $masProjectCaseCode = \Civi\Api4\CiviCase::get(false)
                ->addSelect('Projects.MAS_Project_Case_Code')
                ->addWhere('id', '=', $caseId)
                ->execute()
                ->first()['Projects.MAS_Project_Case_Code'] ?? null;
                ;

                // If the MAS Project code is empty, check for preserved code first
                if (empty($masProjectCaseCode)) {
                    if (!empty($preservedCodes['MAS_Project_Case_Code'])) {
                        // Use preserved code from merge
                        $masCode = $preservedCodes['MAS_Project_Case_Code'];
                        \Civi::log()->info("MASCode: Using preserved Project case code {$masCode} for case {$caseId}");
                    } else {
                        // Generate the MAS code
                        $masCode = CodeGenerator::generate($caseType);
                    }

                    // Update the case with the MAS Code
                    $result = \Civi\Api4\CiviCase::update(false)
                        ->addValue('Projects.MAS_Project_Case_Code', $masCode)
                        ->addWhere('id', '=', $caseId)
                        ->execute();
                }
            }
        }
    }

    /**
     * Check for preserved MAS codes from contact merge process.
     * 
     * @param int $caseId
     * @return array
     */
    private function getPreservedCodesForCase(int $caseId): array
    {
        // Try to find preserved codes for recently merged cases
        // We check multiple possible original case IDs since we don't have direct mapping
        for ($i = 1; $i <= 10; $i++) {
            $originalCaseId = $caseId - $i;
            if ($originalCaseId > 0) {
                $preservedCodes = CaseMergeHook::getPreservedCodes($originalCaseId);
                if (!empty($preservedCodes)) {
                    return $preservedCodes;
                }
            }
        }
        
        return [];
    }

    /**
     * Method to return extra form elements for action
     *
     * @param int $ruleActionId
     * @return bool
     * @access public
     */
    public function getExtraDataInputUrl($ruleActionId)
    {
        return false;
    }
}
