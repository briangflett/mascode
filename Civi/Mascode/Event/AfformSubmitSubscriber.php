<?php
// File: Civi/Mascode/Event/AfformSubmitSubscriber.php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Afform\Event\AfformSubmitEvent;

class AfformSubmitSubscriber implements EventSubscriberInterface
{

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
     * Process form submission to create relationships
     *
     * @param \Civi\Afform\Event\AfformSubmitEvent $event
     */
    public function onFormSubmit(AfformSubmitEvent $event): void
    {
        $afform = $event->getAfform();
        $formDataModel = $event->getFormDataModel();
        $apiRequest = $event->getApiRequest();
        $entity = $event->getEntity();
        $entityId = $event->getEntityId();
        $entityIds = $event->getEntityIds();
        $entityName = $event->getEntityName();
        $entityType = $event->getEntityType();
        $organizationForCase = $event->getOrganizationForCase();

        $formName = 'test';

        // Only process your specific form
        // Replace 'my_organization_form' with your actual form name
        if ($formName === 'mas_request_for_consulting_assistance_form_-_from_core_objects') {
            $this->createRelationships($event);
        }
    }

    /**
     * Create relationships between organization and individuals
     * 
     * @param \Civi\Afform\Event\AfformSubmitEvent $event
     */
    protected function createRelationships(AfformSubmitEvent $event): void
    {
        $formData = $event->getValues();

        try {
            // Get the organization ID - adjust these paths based on your form structure
            $organizationId = $formData['org'][0]['id'] ?? null;

            // Get the president ID
            $presidentId = $formData['president'][0]['id'] ?? null;

            // Get the executive director ID
            $executiveDirectorId = $formData['executive_director'][0]['id'] ?? null;

            if (!$organizationId) {
                \Civi::log()->error('Organization ID not found in form submission');
                return;
            }

            // Create President relationship if we have both contacts
            if ($presidentId) {
                $this->createRelationship(
                    $presidentId,
                    $organizationId,
                    'Employee of', // Replace with your actual relationship type
                    'President' // Optional relationship description
                );
            }

            // Create Executive Director relationship if we have both contacts
            if ($executiveDirectorId) {
                $this->createRelationship(
                    $executiveDirectorId,
                    $organizationId,
                    'Employee of', // Replace with your actual relationship type
                    'Executive Director' // Optional relationship description
                );
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Error creating relationships: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Create a relationship between two contacts
     * 
     * @param int $contactIdA First contact
     * @param int $contactIdB Second contact
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
            // First find the relationship type ID
            $relType = \Civi\Api4\RelationshipType::get(FALSE)
                ->addSelect('id')
                ->addWhere('name_a_b', '=', $relationshipType)
                ->orWhere('label_a_b', '=', $relationshipType)
                ->execute()
                ->first();

            if (empty($relType['id'])) {
                \Civi::log()->error('Relationship type not found: {type}', [
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

            \Civi::log()->info('Created relationship: {type} between contacts {a} and {b}', [
                'type' => $relationshipType,
                'a' => $contactIdA,
                'b' => $contactIdB,
            ]);

            return $rel['id'] ?? null;
        } catch (\Exception $e) {
            \Civi::log()->error('Failed to create relationship: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return null;
        }
    }
}
