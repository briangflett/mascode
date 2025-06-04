<?php

// File: CRM/Mascode/CiviRules/Form/EmployerRelationship.php

/**
 * Legacy PSR-0 bridge for CiviRules form compatibility
 * Delegates to the PSR-4 class
 */
class CRM_Mascode_CiviRules_Form_EmployerRelationship extends CRM_CivirulesActions_Form_Form
{
    public function buildQuickForm()
    {
        // Delegate to PSR-4 class
        $psr4Form = new \Civi\Mascode\CiviRules\Form\EmployerRelationship();
        $psr4Form->ruleAction = $this->ruleAction;
        return $psr4Form->buildQuickForm();
    }

    public function setDefaultValues()
    {
        // Delegate to PSR-4 class
        $psr4Form = new \Civi\Mascode\CiviRules\Form\EmployerRelationship();
        $psr4Form->ruleAction = $this->ruleAction;
        return $psr4Form->setDefaultValues();
    }

    public function postProcess()
    {
        // Delegate to PSR-4 class
        $psr4Form = new \Civi\Mascode\CiviRules\Form\EmployerRelationship();
        $psr4Form->ruleAction = $this->ruleAction;
        return $psr4Form->postProcess();
    }
}
