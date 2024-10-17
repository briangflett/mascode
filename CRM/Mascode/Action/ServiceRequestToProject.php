<?php

// Define the namespace for the action
namespace CRM_Mascode_Action;

// Include the necessary CiviCRM classes
use CRM_Civirules_Action;
use CRM_Civirules_TriggerData_TriggerData;

// Extend the CRM_Civirules_Action class to define a new action
class ServiceRequestToProject extends CRM_Civirules_Action
{

    /**
     * The method called when this action is triggered by Civirules
     *
     * @param CRM_Civirules_TriggerData_TriggerData $triggerData
     *   The parameters passed from the triggering event.
     */
    public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData)
    {
        // Retrieve the case ID from the triggering event parameters
        $caseId = $triggerData['case_id'];

        // Load the existing service request case using API v4
        $serviceCase = civicrm_api4('Case', 'get', [
            'where' => [['id', '=', $caseId]],
        ])->first();
        if (!$serviceCase) {
            $this->logMessage("Service request case not found: $caseId");
            return;
        }

        // Extract details from the service request that may be needed for the project case
        $contactId = $serviceCase['contact_id'];
        $subject = "Project created from Service Request #" . $serviceCase['id'];

        // Prepare parameters for creating a new project case
        $projectParams = [
            'contact_id' => $contactId,
            'subject' => $subject,
            'case_type_id' => 'project',  // Assuming 'project' is the case type for new projects
            'status_id' => 'Open',
            'start_date' => date('Ymd'),
        ];

        // Create the new project case using API v4
        $result = civicrm_api4('Case', 'create', [
            'values' => $projectParams,
        ]);
        if (is_a($result, 'CRM_Core_Error')) {
            $this->logMessage("Failed to create project case: " . $result->getMessage());
            return;
        }

        $this->logMessage("Project case created successfully for contact: $contactId");
    }

    /**
     * Provide a descriptive label for this action
     *
     * @return string
     *   A label for the action.
     */
    public function getActionLabel()
    {
        return ts('Create Project Case from Service Request');
    }

    /**
     * Provide an extra data input URL if needed for this action
     *
     * @param int $ruleActionId
     *   The rule action ID.
     *
     * @return string|null
     *   A URL for extra data input, or null if none is needed.
     */
    public function getExtraDataInputUrl($ruleActionId)
    {
        return null;
    }
}
