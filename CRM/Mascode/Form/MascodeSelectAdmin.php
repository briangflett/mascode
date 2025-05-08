<?php
// File: CRM/Mascode/Form/MascodeSelectAdmin.php

class CRM_Mascode_Form_MascodeSelectAdmin extends CRM_CivirulesActions_Form_Form
{
    public function buildQuickForm()
    {
        // Delegate to your PSR-4 class
        $psr4Form = new \Civi\Mascode\CiviRules\Form\SelectAdmin();
        $psr4Form->ruleAction = $this->ruleAction;
        return $psr4Form->buildQuickForm();
    }
    
    public function setDefaultValues()
    {
        // Delegate to your PSR-4 class
        $psr4Form = new \Civi\Mascode\CiviRules\Form\SelectAdmin();
        $psr4Form->ruleAction = $this->ruleAction;
        return $psr4Form->setDefaultValues();
    }
    
    public function postProcess()
    {
        // Delegate to your PSR-4 class
        $psr4Form = new \Civi\Mascode\CiviRules\Form\SelectAdmin();
        $psr4Form->ruleAction = $this->ruleAction;
        return $psr4Form->postProcess();
    }
}