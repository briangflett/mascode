<?php

class CRM_Mascode_Action_CustomAction extends CRM_Civirules_Action
{

    /**
     * Method processAction to execute the action
     *
     * @param CRM_Civirules_TriggerData_TriggerData $triggerData
     * @access public
     *
     */
    public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData)
    {
        // Fetch all data related to the triggering entity
        $entityData = $triggerData->getEntityData();

        // Log the data to the CiviCRM log
        CRM_Core_Error::debug_var('Trigger Data', $entityData);

        // Extract the contact ID from the trigger data
        $contactId = $triggerData->getContactId();

        // Implement your custom action logic here
        // For example, creating a case, sending an email, etc.
        $caseParams = [
            'case_type_id' => 'Project', // Replace with your case type ID or name
            'contact_id' => $contactId,
            'subject' => 'New Project Case', // Customize as needed
            'status_id' => 'Open', // Adjust the status as per your requirements
        ];

        // Create the case using the CiviCRM API
        $result = civicrm_api3('Case', 'create', $caseParams);

        if ($result['is_error']) {
            throw new CRM_Civirules_Exception_CivirulesException('Failed to create case: ' . $result['error_message']);
        }
    }

    /**
     * Method to return the url for additional form processing for action
     * and return false if none is needed
     *
     * @param int $ruleActionId
     * @return bool
     * @access public
     */
    public function getExtraDataInputUrl($ruleActionId)
    {
        return FALSE;
    }

    /**
     * Returns a description for this action.
     *
     * @return string
     *   A description of the action.
     */
    public function getDescription()
    {
        return ts('Custom action to create a new project from a service request');
    }
}
