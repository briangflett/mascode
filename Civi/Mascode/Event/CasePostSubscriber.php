<?php
// File: Civi/Mascode/Event/CasePostSubscriber.php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\GenericHookEvent;
use Civi\Mascode\Util\CodeGenerator;

/**
 * Subscribes to post-save case events to perform operations after
 * case data is saved to the database.
 */
class CasePostSubscriber implements EventSubscriberInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        \Civi::log()->debug('CasePostSubscriber instantiated');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_post' => ['onPostSave', 0],
        ];
    }

    /**
     * Process post-save case operations
     *
     * @param \Civi\Core\Event\GenericHookEvent $event
     */
    public function onPostSave(GenericHookEvent $event): void
    {
        $op = $event->op;
        $objectName = $event->objectName;
        $objectId = $event->objectId;
        $objectRef = $event->objectRef;

        // Only process case-related events
        if ($objectName !== 'Case') {
            return;
        }

        try {
            switch ($op) {
                case 'create':
                    $this->handleCaseCreate($objectId, $objectRef);
                    break;
                    
                case 'edit':
                    $this->handleCaseEdit($objectId, $objectRef);
                    break;
                    
                // Add other operations as needed
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Error in CasePostSubscriber::onPostSave: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Handle case creation operation
     *
     * @param int $caseId Case ID
     * @param object $caseObject Case object
     */
    protected function handleCaseCreate(int $caseId, $caseObject): void
    {
        try {
            // Perform any additional operations needed after case creation
            // such as creating default activities, sending notifications, etc.
            
            \Civi::log()->info('Case created: {id}', ['id' => $caseId]);
        } catch (\Exception $e) {
            \Civi::log()->error('Error in post-create case handling: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Handle case edit operation
     *
     * @param int $caseId Case ID
     * @param object $caseObject Case object
     */
    protected function handleCaseEdit(int $caseId, $caseObject): void
    {
        try {
            // Check if we need to create a project for this case
            // (flagged by CasePreSubscriber)
            if (!empty(\Civi::$statics['Civi\Mascode\Event\CasePreSubscriber']['create_project_for_case']) 
                && \Civi::$statics['Civi\Mascode\Event\CasePreSubscriber']['create_project_for_case'] == $caseId) {
                
                $this->createProjectFromServiceRequest($caseId);
                
                // Clear the flag
                unset(\Civi::$statics['Civi\Mascode\Event\CasePreSubscriber']['create_project_for_case']);
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Error in post-edit case handling: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Create a project case from a service request
     *
     * @param int $srCaseId Service request case ID
     * @return int|null ID of the created project, or null on failure
     */
    protected function createProjectFromServiceRequest(int $srCaseId): ?int
    {
        try {
            // Get service request case data
            $srCase = \Civi\Api4\CiviCase::get(FALSE)
                ->addSelect('*', 'custom.*', 'case_contact.contact_id', 'case_type_id:name')
                ->addJoin('CaseContact AS case_contact', 'LEFT')
                ->addWhere('id', '=', $srCaseId)
                ->execute()
                ->first();
                
            if (empty($srCase) || $srCase['case_type_id:name'] !== 'service_request') {
                \Civi::log()->error('Cannot create project: Invalid service request case: {id}', [
                    'id' => $srCaseId,
                ]);
                return null;
            }
            
            // Generate code for new project
            $masCode = CodeGenerator::generate('project');
            
            // Prepare project data
            $subject = $masCode . ' ' . $srCase['subject'];
            $clientId = $srCase['case_contact.contact_id'];
            $practiceArea = $srCase['Cases_SR_Projects_.Practice_Area'] ?? null;
            $relatedSR = $srCase['Cases_SR_Projects_.MAS_SR_Case_Code'] ?? null;
            $startDate = date('Y-m-d');
            
            // Get current user as creator
            $creatorId = \CRM_Core_Session::getLoggedInContactID() ?: 1;
            
            // Create the project case
            $projectCase = \Civi\Api4\CiviCase::create(FALSE)
                ->addValue('case_type_id:name', 'project')
                ->addValue('subject', $subject)
                ->addValue('creator_id', $creatorId)
                ->addValue('start_date', $startDate)
                ->addValue('status_id:name', 'Open')
                ->addValue('contact_id', [$clientId])
                ->execute()
                ->first();
                
            if (empty($projectCase['id'])) {
                throw new \Exception("Failed to create project case");
            }
            
            $projectId = $projectCase['id'];
            
            // Set custom fields for the project
            if ($practiceArea || $relatedSR || $masCode) {
                $this->updateProjectCustomFields($projectId, [
                    'Projects.Practice_Area' => $practiceArea,
                    'MAS_Project_Case_Code' => $masCode,
                    'Related_SR_Case_Code' => $relatedSR,
                ]);
            }
            
            // Link the cases with a relationship
            $this->createCaseRelationship($srCaseId, $projectId);
            
            // Create an activity to record the link
            $this->createLinkCasesActivity($srCaseId, $projectId, $clientId, $creatorId);
            
            \Civi::log()->info('Created project {projectId} from service request {srId}', [
                'projectId' => $projectId,
                'srId' => $srCaseId,
            ]);
            
            return $projectId;
        } catch (\Exception $e) {
            \Civi::log()->error('Error creating project from service request: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            
            return null;
        }
    }
    
    /**
     * Update custom fields for a project case
     *
     * @param int $caseId
     * @param array $customData
     */
    protected function updateProjectCustomFields(int $caseId, array $customData): void
    {
        try {
            $updateParams = [];
            
            foreach ($customData as $field => $value) {
                if (!empty($value)) {
                    $updateParams[$field] = $value;
                }
            }
            
            if (!empty($updateParams)) {
                \Civi\Api4\CiviCase::update(FALSE)
                    ->addWhere('id', '=', $caseId)
                    ->setValues($updateParams)
                    ->execute();
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Error updating project custom fields: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
    
    /**
     * Create relationship between service request and project cases
     *
     * @param int $srCaseId
     * @param int $projectCaseId
     */
    protected function createCaseRelationship(int $srCaseId, int $projectCaseId): void
    {
        try {
            // Get relationship type ID for "Related to"
            $relTypeId = $this->getRelationshipTypeId('Related to');
            
            if (!$relTypeId) {
                throw new \Exception("Cannot find 'Related to' relationship type");
            }
            
            // Create the relationship
            \Civi\Api4\Relationship::create(FALSE)
                ->addValue('relationship_type_id', $relTypeId)
                ->addValue('case_id:name', $srCaseId)
                ->addValue('case_id_b:name', $projectCaseId)
                ->addValue('is_active', TRUE)
                ->execute();
        } catch (\Exception $e) {
            \Civi::log()->error('Error creating case relationship: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
    
    /**
     * Create a "Link Cases" activity to document the connection
     *
     * @param int $srCaseId
     * @param int $projectCaseId
     * @param int $clientId
     * @param int $creatorId
     */
    protected function createLinkCasesActivity(int $srCaseId, int $projectCaseId, int $clientId, int $creatorId): void
    {
        try {
            // Create an activity
            $activity = \Civi\Api4\Activity::create(FALSE)
                ->addValue('activity_type_id:name', 'Link Cases')
                ->addValue('subject', "Linked Service Request (ID: {$srCaseId}) with Project (ID: {$projectCaseId})")
                ->addValue('source_contact_id', $creatorId)
                ->addValue('target_contact_id', [$clientId])
                ->addValue('status_id:name', 'Completed')
                ->execute()
                ->first();
                
            if (empty($activity['id'])) {
                throw new \Exception("Failed to create link activity");
            }
            
            // Connect activity to both cases
            \Civi\Api4\CaseActivity::create(FALSE)
                ->addValue('case_id', $srCaseId)
                ->addValue('activity_id', $activity['id'])
                ->execute();
                
            \Civi\Api4\CaseActivity::create(FALSE)
                ->addValue('case_id', $projectCaseId)
                ->addValue('activity_id', $activity['id'])
                ->execute();
        } catch (\Exception $e) {
            \Civi::log()->error('Error creating link activity: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
    
    /**
     * Get relationship type ID by name
     *
     * @param string $name
     * @return int|null
     */
    protected function getRelationshipTypeId(string $name): ?int
    {
        try {
            $relType = \Civi\Api4\RelationshipType::get(FALSE)
                ->addSelect('id')
                ->addWhere('name_a_b', '=', $name)
                ->orWhere('label_a_b', '=', $name)
                ->execute()
                ->first();
                
            return $relType['id'] ?? null;
        } catch (\Exception $e) {
            \Civi::log()->error('Error getting relationship type: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            
            return null;
        }
    }
}