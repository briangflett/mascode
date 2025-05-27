<?php
// File: Civi/Mascode/Event/AfformSubmitSubscriber.php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Afform\Event\AfformSubmitEvent;

class AfformSubmitSubscriber implements EventSubscriberInterface
{
    /**
     * Store entity IDs during form submission processing
     * Structure: [session_id => ['organization_id' => X, 'president_id' => Y, 'executive_director_id' => Z]]
     */
    private static array $submissionData = [];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'civi.afform.submit' => ['onFormSubmit', 0],
        ];
    }

    /**
     * Process form submission to collect entity IDs and create relationships
     *
     * @param \Civi\Afform\Event\AfformSubmitEvent $event
     */
    public function onFormSubmit(AfformSubmitEvent $event): void
    {
        $afform = $event->getAfform();
        $formRoute = $afform['server_route'] ?? null;

        // Check if this is our target form
        if ($formRoute !== 'civicrm/mas-rcs-form') {
            return;
        }

        $entityName = $event->getEntityName();
        $entityId = $event->getEntityId();

        // *** EntityId is null if the entity is being created by this submission ***

        \Civi::log()->debug('AfformSubmitSubscriber: Processing entity', [
            'entity_name' => $entityName,
            'entity_id' => $entityId
        ]);

        // Get or create submission tracking data
        $sessionId = $this->getSessionId();
        if (!isset(self::$submissionData[$sessionId])) {
            self::$submissionData[$sessionId] = [];
        }

        // Store entity IDs based on entity name
        switch ($entityName) {
            case 'Organization1':
                self::$submissionData[$sessionId]['organization_id'] = $entityId;
                break;
            case 'Individual1': // President
                self::$submissionData[$sessionId]['president_id'] = $entityId;
                break;
            case 'Individual2': // Executive Director
                self::$submissionData[$sessionId]['executive_director_id'] = $entityId;
                break;
            case 'Case1':
                // Create relationships when processing Case1 (last entity processed)
                $this->createRelationships($sessionId);
                // Clean up after processing
                unset(self::$submissionData[$sessionId]);
                break;
        }
    }

    /**
     * Get unique session identifier for this submission
     */
    private function getSessionId(): string
    {
        $sessionId = session_id();
        if (!$sessionId) {
            // Fallback if no session (e.g., in testing)
            $sessionId = 'no-session-' . getmypid() . '-' . time();
        }
        return $sessionId;
    }

    /**
     * Create relationships between organization and individuals
     * 
     * @param string $sessionId
     */
    protected function createRelationships(string $sessionId): void
    {
        if (!isset(self::$submissionData[$sessionId])) {
            \Civi::log()->warning('AfformSubmitSubscriber: No submission data found', [
                'session_id' => $sessionId
            ]);
            return;
        }

        $data = self::$submissionData[$sessionId];

        try {
            $organizationId = $data['organization_id'] ?? null;
            $presidentId = $data['president_id'] ?? null;
            $executiveDirectorId = $data['executive_director_id'] ?? null;

            if (!$organizationId) {
                \Civi::log()->warning('AfformSubmitSubscriber: Organization ID not found in submission data');
                return;
            }

            \Civi::log()->info('AfformSubmitSubscriber: Creating relationships', [
                'organization_id' => $organizationId,
                'president_id' => $presidentId,
                'executive_director_id' => $executiveDirectorId
            ]);

            // Create President relationship if we have both contacts
            if ($presidentId) {
                $this->createRelationship(
                    $presidentId,
                    $organizationId,
                    'President of',
                    'President relationship created via MAS RCS Form'
                );
            }

            // Create Executive Director relationship if we have both contacts
            if ($executiveDirectorId) {
                $this->createRelationship(
                    $executiveDirectorId,
                    $organizationId,
                    'Executive Director of',
                    'Executive Director relationship created via MAS RCS Form'
                );
            }
        } catch (\Exception $e) {
            \Civi::log()->error('AfformSubmitSubscriber: Error creating relationships', [
                'message' => $e->getMessage(),
                'exception' => $e,
                'session_id' => $sessionId
            ]);
        }
    }

    /**
     * Create a relationship between two contacts
     * 
     * @param int $contactIdA First contact (individual)
     * @param int $contactIdB Second contact (organization)
     * @param string $relationshipType Name or label of relationship type
     * @param string $description Optional description
     * @return int|null ID of created relationship or null on failure
     */
    protected function createRelationship(
        int $contactIdA,
        int $contactIdB,
        string $relationshipType,
        string $description = ''
    ): ?int {
        try {
            // Check if relationship already exists
            $existing = \Civi\Api4\Relationship::get(FALSE)
                ->addSelect('id')
                ->addWhere('contact_id_a', '=', $contactIdA)
                ->addWhere('contact_id_b', '=', $contactIdB)
                ->addWhere('relationship_type_id.name_a_b', '=', $relationshipType)
                ->addWhere('is_active', '=', TRUE)
                ->execute()
                ->first();

            if ($existing) {
                \Civi::log()->info('AfformSubmitSubscriber: Relationship already exists', [
                    'relationship_id' => $existing['id'],
                    'type' => $relationshipType,
                    'contact_a' => $contactIdA,
                    'contact_b' => $contactIdB
                ]);
                return $existing['id'];
            }

            // Find the relationship type ID
            $relType = \Civi\Api4\RelationshipType::get(FALSE)
                ->addSelect('id')
                ->addWhere('name_a_b', '=', $relationshipType)
                ->orWhere('label_a_b', '=', $relationshipType)
                ->execute()
                ->first();

            if (empty($relType['id'])) {
                \Civi::log()->error('AfformSubmitSubscriber: Relationship type not found', [
                    'type' => $relationshipType,
                ]);
                return null;
            }

            // Create the relationship
            $rel = \Civi\Api4\Relationship::create(FALSE)
                ->addValue('relationship_type_id', $relType['id'])
                ->addValue('contact_id_a', $contactIdA)
                ->addValue('contact_id_b', $contactIdB)
                ->addValue('is_active', TRUE)
                ->addValue('description', $description)
                ->execute()
                ->first();

            \Civi::log()->info('AfformSubmitSubscriber: Created relationship', [
                'relationship_id' => $rel['id'],
                'type' => $relationshipType,
                'contact_a' => $contactIdA,
                'contact_b' => $contactIdB
            ]);

            return $rel['id'] ?? null;
        } catch (\Exception $e) {
            \Civi::log()->error('AfformSubmitSubscriber: Failed to create relationship', [
                'message' => $e->getMessage(),
                'type' => $relationshipType,
                'contact_a' => $contactIdA,
                'contact_b' => $contactIdB,
                'exception' => $e,
            ]);

            return null;
        }
    }
}
