<?php
// file: Civi/Mascode/CiviRules/Form/SelectAdmin.php

namespace Civi\Mascode\CiviRules\Form;

use Civi\Mascode\CiviRules\Form\FormBase;

/**
 * Form for selecting an admin contact for a CiviRule action
 */
class SelectAdmin extends FormBase
{
    /**
     * Builds the form
     */
    public function buildQuickForm()
    {
        $this->add('hidden', 'rule_action_id');
        
        // Add contact search field
        $this->addEntityRef('admin_contact_id', ts('MAS Administrator'), [
            'entity' => 'Contact',
            'placeholder' => ts('- Select Contact -'),
            'select' => ['minimumInputLength' => 0],
            'api' => [
                'params' => [
                    'contact_type' => 'Individual',
                ],
            ],
        ], TRUE);
        
        $this->addButtons([
            ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
            ['type' => 'cancel', 'name' => ts('Cancel')],
        ]);
    }
    
    /**
     * Set default values for the form
     */
    public function setDefaultValues()
    {
        $defaultValues = parent::setDefaultValues();
        $data = unserialize($this->ruleAction->action_params);
        
        if (!empty($data['admin_contact_id'])) {
            $defaultValues['admin_contact_id'] = $data['admin_contact_id'];
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
            'admin_contact_id' => $values['admin_contact_id'],
        ];
        
        $ruleAction = new \CRM_Civirules_BAO_RuleAction();
        $ruleAction->id = $ruleActionId;
        $ruleAction->action_params = serialize($params);
        $ruleAction->save();
        
        $redirectUrl = \CRM_Utils_System::url('civicrm/civirule/form/rule', 
            'action=update&id=' . $this->ruleAction->rule_id);
        \CRM_Utils_System::redirect($redirectUrl);
    }
}