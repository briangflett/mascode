<?php

// file: Civi/Mascode/CiviRules/Action/PresidentRelationship.php

namespace Civi\Mascode\CiviRules\Action;

class PresidentRelationship extends \CRM_Civirules_Action
{
    /**
     * Method to execute the action
     *
     * @param \CRM_Civirules_TriggerData_TriggerData $triggerData
     * @access public
     */
    public function processAction(\CRM_Civirules_TriggerData_TriggerData $triggerData)
    {
        $contactId = $triggerData->getContactId();
        if (empty($contactId)) {
            throw new \Exception("Contact ID not found in trigger");
        }

        $individual = $triggerData->getEntityData('Individual');
        if (empty($individual)) {
            throw new \Exception("Individual not found in trigger");
        }

        $organizationId = $individual['employer_id'] ?? null;
        if (empty($organizationId)) {
            \Civi::log()->info('PresidentRelationship: No employer_id found for contact', ['contact_id' => $contactId]);
            return; // No employer, nothing to do
        }

        try {
            // Check if there is already a president relationship between the individual and the organization
            $existingRelationship = \Civi\Api4\Relationship::get(false)
                ->addSelect('id', 'is_active')
                ->addWhere('contact_id_a', '=', $contactId)
                ->addWhere('contact_id_b', '=', $organizationId)
                ->addWhere('relationship_type_id.name_a_b', '=', 'President of')
                ->addWhere('is_active', '=', true)
                ->setLimit(1)
                ->execute()
                ->first();

            if ($existingRelationship) {
                \Civi::log()->info('PresidentRelationship: Active President relationship already exists', [
                    'relationship_id' => $existingRelationship['id'],
                    'contact_id' => $contactId,
                    'organization_id' => $organizationId
                ]);
                return; // Relationship already exists
            }

            // Get the relationship type ID for "President of"
            $relationshipType = \Civi\Api4\RelationshipType::get(false)
                ->addSelect('id')
                ->addClause(
                    'OR',
                    ['name_a_b', '=', 'President of'],
                    ['label_a_b', '=', 'President of']
                )
                ->setLimit(1)
                ->execute()
                ->first();

            if (!$relationshipType) {
                throw new \Exception('Relationship type "President of" not found');
            }

            // Create the president relationship
            $newRelationship = \Civi\Api4\Relationship::create(false)
                ->addValue('relationship_type_id', $relationshipType['id'])
                ->addValue('contact_id_a', $contactId)
                ->addValue('contact_id_b', $organizationId)
                ->addValue('is_active', true)
                ->addValue('description', 'President relationship created by CiviRules')
                ->execute()
                ->first();

            \Civi::log()->info('PresidentRelationship: Created new President relationship', [
                'relationship_id' => $newRelationship['id'],
                'contact_id' => $contactId,
                'organization_id' => $organizationId
            ]);

        } catch (\Exception $e) {
            \Civi::log()->error('PresidentRelationship: Error processing action', [
                'message' => $e->getMessage(),
                'contact_id' => $contactId,
                'organization_id' => $organizationId,
                'exception' => $e
            ]);
            throw new \Exception('Error creating President relationship: ' . $e->getMessage());
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
        return false;
    }

    /**
     * Returns a user friendly text explaining the action
     *
     * @return string
     * @access public
     */
    public function userFriendlyConditionParams()
    {
        return 'Create President relationship between Individual and their Employer Organization';
    }
}
