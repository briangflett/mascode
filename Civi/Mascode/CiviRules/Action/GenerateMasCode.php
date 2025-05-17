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

            $masSrCaseCode = \Civi\Api4\CiviCase::get(TRUE)
            ->addSelect('Cases_SR_Projects_.MAS_SR_Case_Code')
            ->addWhere('id', '=', $caseId)
            ->execute()
            ->first()['Cases_SR_Projects_.MAS_SR_Case_Code'] ?? null;;

            // If the MAS SR code is empty, generate a new one
            if (empty($masSrCaseCode)) {
                // Generate the MAS code
                $masCode = CodeGenerator::generate($caseType);
                
                // Update the case with the MAS Code
                $result = \Civi\Api4\CiviCase::update(TRUE)
                    ->addValue('Cases_SR_Projects_.MAS_SR_Case_Code', $masCode)
                    ->addWhere('id', '=', $caseId)
                    ->execute();
         }

        } else {

            if ($caseType ==  'project') {

                $masProjectCaseCode = \Civi\Api4\CiviCase::get(TRUE)
                ->addSelect('Projects.MAS_Project_Case_Code')
                ->addWhere('id', '=', $caseId)
                ->execute()
                ->first()['Projects.MAS_Project_Case_Code'] ?? null;;
    
                // If the MAS SR code is empty, generate a new one
                if (empty($masProjectCaseCode)) {
                    // Generate the MAS code
                    $masCode = CodeGenerator::generate($caseType);
                    
                    // Update the case with the MAS Code
                    $result = \Civi\Api4\CiviCase::update(TRUE)
                        ->addValue('Projects.MAS_Project_Case_Code', $masCode)
                        ->addWhere('id', '=', $caseId)
                        ->execute();
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