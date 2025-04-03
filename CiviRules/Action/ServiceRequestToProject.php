<?php

namespace Civi\Mascode\CiviRules\Action;

use CRM_Civirules_Action;
use CRM_Civirules_TriggerData_TriggerData;

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
        // Retrieve the entity data and the action parameters if applicable
        $srCase = $triggerData->getEntityData('Case');
        // $actionParameters = $this->getActionParameters();
        var_dump($srCase);

        // It may be redundant to...
        // Check if it is a case of type service request
        // Check if the status has changed to "Project Created"

        // Extract details from the service request that may be needed for the project case
        $pSubject = "P: " . $srCase['subject'];
        $pStartDate = date('Y-m-d');

        // Check if contacts array exists, throw exception if not
        if (!isset($srCase['contacts']) || !is_array($srCase['contacts'])) {
            throw new \Exception("Contacts array not found in case data.");
        }

        $clientContactId = null;
        $coordinatorContactId = null;

        foreach ($srCase['contacts'] as $contact) {
            if (isset($contact['role'])) {
                if ($contact['role'] === 'Client' && !$clientContactId) {
                    $clientContactId = $contact['contact_id'] ?? null;
                } elseif ($contact['role'] === 'Case Coordinator for' && !$coordinatorContactId) {
                    $coordinatorContactId = $contact['contact_id'] ?? null;
                }

                // Break the loop if we've found both contacts
                if ($clientContactId && $coordinatorContactId) {
                    break;
                }
            }
        }


        // Create the project
        $civiCase = \Civi\Api4\CiviCase::create(TRUE)
            ->addValue('case_type_id.name', 'project')
            ->addValue('subject', $pSubject)
            ->addValue('creator_id', $coordinatorContactId)
            ->addValue('start_date', $pStartDate)
            ->addValue('status_id:label', 'Active')
            ->addValue(
                'contact_id',
                [
                    $clientContactId,
                ]
            )
            ->execute();

        $srCase_id = $srCase['id'];
        $pCase_id = $civiCase[0]['id'];

        // Create a Link Cases activity, and link it to one case
        $civiActivity = \Civi\Api4\Activity::create(TRUE)
            ->addValue('activity_type_id:label', 'Link Cases')
            ->addValue('source_contact_id', $coordinatorContactId)
            ->addValue('target_contact_id', [
                $clientContactId,
            ])
            ->addValue('case_id', $pCase_id)
            ->addValue('status_id:label', 'Completed')
            ->addValue('subject', 'Create link between - Service Request (CaseID: ' . $srCase_id . ') and Project (CaseID: ' . $pCase_id . ').')
            ->execute();

        $activity_id = $civiActivity[0]['id'];

        // Then link the activity to the other case
        $civiCaseActivity = \Civi\Api4\CaseActivity::create(TRUE)
            ->addValue('case_id', $srCase_id)
            ->addValue('activity_id', $activity_id)
            ->execute();
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
