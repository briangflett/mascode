<?php
// file: Civi/Mascode/CiviRules/Action/GenerateMasCode.php

namespace Civi\Mascode\CiviRules\Action;

use Civi\Mascode\Util\CodeGenerator;

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
        $caseType = \Civi\Api4\CaseType::get()
            ->addSelect('name')
            ->addWhere('id', '=', $caseTypeId)
            ->execute()
            ->first()['name'] ?? null;

        // Generate MAS SR codes for all new service requests
        if ($caseType == 'service_request') {
            $fieldId = CodeGenerator::getFieldId('Cases_SR_Projects_', 'MAS_SR_Case_Code');

            // Check if the MAS SR code already exists
            $masSrCaseCode = \Civi\Api4\CiviCase::get()
                ->addSelect("custom_{$fieldId}")
                ->addWhere('id', '=', $caseId)
                ->execute()
                ->first()["custom_{$fieldId}"] ?? null;

            // If the MAS SR code is empty, generate a new one
            if (empty($masSrCaseCode)) {
                // Generate the MAS code
                $masCode = CodeGenerator::generate($caseType);
                
                // Update the case with the MAS Code
                if ($fieldId) {
                    $result = \Civi\Api4\CiviCase::update()
                        ->addValue('id', $case['id'])
                        ->addValue("custom_{$fieldId}", $masCode)
                        ->execute();
                }
            }

        } else {

            if ($caseType ==  'project') {
                $fieldId = CodeGenerator::getFieldId('Projects', 'MAS_Project_Case_Code');

                // Check if the MAS project code already exists
                $masProjecCaseCode = \Civi\Api4\CiviCase::get()
                    ->addSelect("custom_{$fieldId}")
                    ->addWhere('id', '=', $caseId)
                    ->execute()
                    ->first()["custom_{$fieldId}"] ?? null;

                // If the MAS project code is empty, generate a new one
                if (empty($masProjecCaseCode)) {
                    // Generate the MAS code
                    $masCode = CodeGenerator::generate($caseType);
                    
                    // Update the case with the MAS Code
                    if ($fieldId) {
                        $result = \Civi\Api4\CiviCase::update()
                            ->addValue('id', $case['id'])
                            ->addValue("custom_{$fieldId}", $masCode)
                            ->execute();
                    }
                }
            }
        }
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
        return FALSE;
    }
}