<?php
// file: Civi/Mascode/CiviRules/Action/ServiceRequestToProject.php

namespace Civi\Mascode\CiviRules\Action;

use Civi\Mascode\Util\CodeGenerator;

use function ElementorProDeps\DI\get;

class ServiceRequestToProject extends \CRM_Civirules_Action
{

    /**
     * The method called when this action is triggered by Civirules
     *
     * @param \CRM_Civirules_TriggerData_TriggerData $triggerData
     *   The parameters passed from the triggering event.
     */
    public function processAction(\CRM_Civirules_TriggerData_TriggerData $triggerData)
    {
        // Retrieve the entity data and the action parameters if applicable
        $srCase = $triggerData->getEntityData('Case');
        $srCaseId = $srCase['id'];

        // I had lots of issues with forms, so I am hard coding the action parameters.
        // $actionParameters = $this->getActionParameters();
        $adminId = \Civi::settings(get('mascode_admin_contact_id')) ?? null;

        // Log the $srCase and $adminId using Civi::log
        \Civi::log()->info('Service Request Case Data:', ['srCase' => $srCase]);
        \Civi::log()->info('Admin Contact ID:', ['adminId' => $adminId]);

        if (empty($srCaseId) || empty($adminId)) {
            throw new \Exception("Service Request ID or Admin Contact ID missing.");
        }

        // CiviCase already...
        // Checked if it is a case of type service request
        // Checked if the status has changed to "Project Created"

        // Extract details from the service request that may be needed for the project case
        $pSubject = "P: " . $srCase['subject'];
        $pStartDate = date('Y-m-d');
        $srCodeFieldId = CodeGenerator::getFieldId('Cases_SR_Projects_', 'MAS_SR_Case_Code');
        $srCaseCode = $srCase["custom_{$srCodeFieldId}"] ?? null;

        // Check if contacts array exists, throw exception if not
        if (!isset($srCase['contacts']) || !is_array($srCase['contacts'])) {
            throw new \Exception("Contacts array not found in case data.");
        }

        $clientContactId = null;
        $coordinatorContactId = null;
        $clientRepContactId = null;

        foreach ($srCase['contacts'] as $contact) {
            if (isset($contact['role'])) {
                if ($contact['role'] === 'Client' && !$clientContactId) {
                    $clientContactId = $contact['contact_id'] ?? null;
                } elseif ($contact['role'] === 'Case Coordinator for' && !$coordinatorContactId) {
                    $coordinatorContactId = $contact['contact_id'] ?? null;
                } elseif ($contact['role'] === 'Case Client Rep for' && !$clientRepContactId) {
                    $coordinatorContactId = $contact['contact_id'] ?? null;
                }
                // Break the loop if we've found all three contacts
                if ($clientContactId && $coordinatorContactId && $clientRepContactId) {
                    break;
                }
            }
        }

        if (!$clientContactId) {
            throw new \Exception("Missing client contact ID.");
        }

        // Generate the MAS code
        $pCaseCode = CodeGenerator::generate('project');

        // Create the project
        $civiCase = \Civi\Api4\CiviCase::create(TRUE)
            ->addValue('case_type_id.name', 'project')
            ->addValue('subject', $pSubject)
            ->addValue('creator_id', $adminId)
            ->addValue('start_date', $pStartDate)
            ->addValue('status_id:name', 'Active')
            ->addValue('Projects.MAS_Project_Case_Code', $pCaseCode)
            ->addValue('Projects.Related_SR_Case_Code', $srCaseCode)
            ->addValue(
                'contact_id',
                [
                    $clientContactId,
                ]
            )
            ->execute();

        $pCaseId = $civiCase[0]['id'];

        if (empty($pCaseId)) {
            throw new \Exception("Project case creation failed.");
        }

        // Update the service request
        $civiCase = \Civi\Api4\CiviCase::update(TRUE)
            ->addValue('Cases_SR_Projects_.Related_Project_Case_Code', $pCaseCode)
            ->addWhere('id', '=', $srCaseId)
            ->execute();

        // Create a Link Cases activity, and link it to one case
        $civiActivity = \Civi\Api4\Activity::create(TRUE)
            ->addValue('activity_type_id:label', 'Link Cases')
            ->addValue('source_contact_id', $adminId)
            ->addValue('target_contact_id', [
                $clientContactId,
            ])
            ->addValue('case_id', $pCaseId)
            ->addValue('status_id:label', 'Completed')
            ->addValue('subject', 'Create link between - Service Request (' . $srCaseCode . ') and Project (' . $pCaseCode . ').')
            ->execute();

        $activity_id = $civiActivity[0]['id'];

        // Then link the activity to the other case
        $civiCaseActivity = \Civi\Api4\CaseActivity::create(TRUE)
            ->addValue('case_id', $srCaseId)
            ->addValue('activity_id', $activity_id)
            ->execute();

        // Set the project client rep to the service request client rep
        if ($clientRepContactId) {
            try {
                $civiRelationship = \Civi\Api4\Relationship::create(TRUE)
                    ->addValue('contact_id_a', $clientRepContactId)     // client rep
                    ->addValue('contact_id_b', $clientContactId)     // client
                    ->addValue('relationship_type_id:label', 'Case Client Rep is')
                    ->addValue('is_active', TRUE)  // depends on project
                    ->addValue('case_id', $pCaseId)
                    ->execute();
            } catch (\Exception $e) {
                // Handle duplicate error or log the message
                \Civi::log()->error("Error creating Client relationship: " . $e->getMessage() . " for Case:$pCaseId Client:$clientContactId Client Rep:$clientRepContactId<br>");
            }
        }

        // Not sure if we should set the project case coordinator to the service request case coordinator (MAS Rep)
        if ($coordinatorContactId) {
        }
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
        // I had lots of issues with forms, so I am hard coding the values.
        // return \CRM_Utils_System::url(
        //     'civicrm/mascode/form/mascodeselectadmin',
        //     'rule_action_id=' . $ruleActionId
        // );
        return FALSE;
    }

    /**
     * Returns a user friendly text explaining the condition params
     * e.g. 'Older than 65'
     *
     * @return string
     * @access public
     */
    public function userFriendlyConditionParams()
    {
        // $params = $this->getActionParameters();
        $label = ts('Set MAS administrator to ID: ' . \Civi::settings(get('mascode_admin_contact_id')));
        return $label;
    }
}
