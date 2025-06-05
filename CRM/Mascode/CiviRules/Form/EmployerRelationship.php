<?php

use CRM_Mascode_ExtensionUtil as E;

/**
 * Form for configuring the Employer Relationship action
 * Only needs to collect the relationship type since contact is determined dynamically
 */
class CRM_Mascode_CiviRules_Form_EmployerRelationship extends CRM_CivirulesActions_Form_Form
{
    /**
     * Build the form
     */
    public function buildQuickForm()
    {
        $this->add('hidden', 'rule_action_id');

        // Get relationship types for dropdown
        $relationshipTypes = $this->getRelationshipTypes();

        $this->add(
            'select',
            'relationship_type_id',
            E::ts('Relationship Type'),
            ['' => E::ts('- Select Relationship Type -')] + $relationshipTypes,
            true
        );

        $this->addButtons([
            ['type' => 'next', 'name' => E::ts('Save'), 'isDefault' => true],
            ['type' => 'cancel', 'name' => E::ts('Cancel')],
        ]);
    }

    /**
     * Set default values for the form
     */
    public function setDefaultValues()
    {
        $defaultValues = parent::setDefaultValues();
        $data = unserialize($this->ruleAction->action_params ?? '');

        if (!empty($data['relationship_type_id'])) {
            $defaultValues['relationship_type_id'] = $data['relationship_type_id'];
        }

        return $defaultValues;
    }

    /**
     * Process form submission
     */
    public function postProcess()
    {
        $values = $this->exportValues();
        $ruleActionId = $this->ruleAction->id;

        $params = [
            'relationship_type_id' => $values['relationship_type_id'],
        ];

        try {
            // Use CiviCRM API to update the rule action parameters
            civicrm_api3('CiviRuleRuleAction', 'create', [
                'id' => $ruleActionId,
                'action_params' => serialize($params),
            ]);

            Civi::log()->info('EmployerRelationship Form: Action parameters saved', [
                'rule_action_id' => $ruleActionId,
                'params' => $params
            ]);
        } catch (Exception $e) {
            Civi::log()->error('EmployerRelationship Form: Could not save action parameters', [
                'rule_action_id' => $ruleActionId,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Could not save action parameters: ' . $e->getMessage());
        }

        $redirectUrl = CRM_Utils_System::url(
            'civicrm/civirule/form/rule',
            'action=update&id=' . $this->ruleAction->rule_id
        );
        CRM_Utils_System::redirect($redirectUrl);
    }

    /**
     * Get available relationship types
     *
     * @return array
     */
    protected function getRelationshipTypes()
    {
        $relationshipTypes = [];

        try {
            $types = \Civi\Api4\RelationshipType::get(false)
                ->addSelect('id', 'label_a_b', 'label_b_a')
                ->addWhere('is_active', '=', true)
                ->execute();

            foreach ($types as $type) {
                // Only provide a_b direction (Individual -> Organization)
                // Since we're always going Individual to Employer
                $relationshipTypes[$type['id']] = $type['label_a_b'] ?? '';
            }
        } catch (Exception $e) {
            Civi::log()->error('Error loading relationship types: ' . $e->getMessage());
        }

        return $relationshipTypes;
    }
}
