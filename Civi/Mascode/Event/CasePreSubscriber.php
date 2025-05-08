<?php
// File: Civi/Mascode/Event/CasePreSubscriber.php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\GenericHookEvent;
use Civi\Mascode\Util\CodeGenerator;

/**
 * Subscribes to pre-save case events to perform operations before
 * case data is saved to the database.
 */
class CasePreSubscriber implements EventSubscriberInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        \Civi::log()->debug('CasePreSubscriber instantiated');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_pre' => ['onPreSave', 0],
        ];
    }

    /**
     * Process pre-save case operations
     *
     * @param \Civi\Core\Event\GenericHookEvent $event
     */
    public function onPreSave(GenericHookEvent $event): void
    {
        $op = $event->op;
        $objectName = $event->objectName;
        $id = $event->id;
        $params = &$event->params;

        // Only process case-related events
        if ($objectName !== 'Case') {
            return;
        }

        try {
            switch ($op) {
                case 'create':
                    $this->handleCaseCreate($params);
                    break;
                    
                case 'edit':
                    $this->handleCaseEdit($id, $params);
                    break;
                    
                // Add other operations as needed
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Error in CasePreSubscriber::onPreSave: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Handle case creation operation
     *
     * @param array $params Case parameters
     */
    protected function handleCaseCreate(array &$params): void
    {
        // Get case type
        $caseTypeId = $params['case_type_id'] ?? null;
        if (!$caseTypeId) {
            return;
        }

        $caseType = $this->getCaseTypeName($caseTypeId);
        
        // Only process service requests and projects
        if (!in_array($caseType, ['service_request', 'project'])) {
            return;
        }
            
        // Generate the MAS code
        $masCode = CodeGenerator::generate($caseType);
        
        // Get the custom field ID
        if ($caseType == 'service_request') {
            $fieldId = CodeGenerator::getFieldId('Cases_SR_Projects_', 'MAS_SR_Case_Code');
        } else {
            $fieldId = CodeGenerator::getFieldId('Projects', 'MAS_Project_Case_Code');
        }

        // Update the MAS Case Code if field ID exists
        if ($codeFieldId) {
            $params["custom_{$codeFieldId}"] = $masCode;
            \Civi::log()->info('Generated MAS code {code} for new {type}', [
                'code' => $masCode,
                'type' => $caseType,
            ]);
        }
    }

    /**
     * Handle case edit operation
     *
     * @param int $id Case ID
     * @param array $params Case parameters
     */
    protected function handleCaseEdit(int $id, array &$params): void
    {
        // Get case type
        $caseTypeId = $params['case_type_id'] ?? null;
        if (!$caseTypeId) {
            return;
        }

        $caseType = $this->getCaseTypeName($caseTypeId);
        
        // Only process service requests
        if ($caseType !== 'service_request') {
            return;
        }
            
        // Check if status is changing
        $caseStatusId = $params['status_id'] ?? null;
        if (!$caseStatusId) {
            return;
        }
        
        $caseStatus = $this->getCaseStatusName($caseStatusId);
        
        // If status is changing to "Project Created", check if we need to create a project
        if ($caseStatus === 'Project Created') {
            $this->checkAndPrepareProjectCreation($id);
        }
    }

    /**
     * Check if a project needs to be created and prepare for it
     *
     * @param int $caseId Service request case ID
     */
    protected function checkAndPrepareProjectCreation(int $caseId): void
    {
        try {
            // Get the pre-existing case information
            $civiCase = \Civi\Api4\CiviCase::get()
                ->addSelect('*', 'custom.*', 'case_contact.contact_id')
                ->addJoin('CaseContact AS case_contact', 'LEFT')
                ->addWhere('id', '=', $caseId)
                ->execute()
                ->first();
                
            // Check if there's already a related project
            if (empty($civiCase['Cases_SR_Projects_.Related_Project_ID'])) {
                // Add a flag to indicate we need to create a project in the post hook
                // We'll use a static property or session variable to pass this info
                // to the CasePostSubscriber
                
                \Civi::$statics[__CLASS__]['create_project_for_case'] = $caseId;
                
                \Civi::log()->info('Flagging service request {id} for project creation', [
                    'id' => $caseId,
                ]);
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Error checking for project creation: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Get case type name from ID
     *
     * @param int|string $caseTypeId
     * @return string|null
     */
    protected function getCaseTypeName($caseTypeId): ?string
    {
        try {
            $caseType = \Civi\Api4\CaseType::get()
                ->addSelect('name')
                ->addWhere('id', '=', $caseTypeId)
                ->execute()
                ->first();
                
            return $caseType['name'] ?? null;
        } catch (\Exception $e) {
            \Civi::log()->error('Error retrieving case type: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            
            return null;
        }
    }

    /**
     * Get case status name from ID
     *
     * @param int|string $statusId
     * @return string|null
     */
    protected function getCaseStatusName($statusId): ?string
    {
        try {
            $caseStatus = \Civi\Api4\OptionValue::get()
                ->addSelect('name')
                ->addWhere('option_group_id:name', '=', 'case_status')
                ->addWhere('value', '=', $statusId)
                ->execute()
                ->first();
                
            return $caseStatus['name'] ?? null;
        } catch (\Exception $e) {
            \Civi::log()->error('Error retrieving case status: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            
            return null;
        }
    }
}