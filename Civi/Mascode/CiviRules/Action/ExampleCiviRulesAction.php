<?php

namespace Civi\Mascode\CiviRules\Action;

use CRM_Civirules_Action;
use CRM_Civirules_TriggerData_TriggerData;
use CRM_Civirules_Utils;

/**
 * Class Example - example CiviRules Action
 *
 * @package Civi\Mascode\CiviRules\Action
 * @author Brian Flett <brian.g.flett@gmail.com>
 * @date 23 Apr 2025
 * @license AGPL-3.0
 * For more info see
 *    https://docs.civicrm.org/civirules/en/latest/create-your-own-introduction/
 *    https://docs.civicrm.org/civirules/en/latest/create-your-own-action/ 
 */
class ExampleCiviRulesAction extends CRM_Civirules_Action {

  /**
   * Method processAction to execute the action
   *
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   * @access public
   *
   */
  public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData) {
    $contactId = $triggerData->getContactId();

    // do stuff here, for example...
    // if (CRM_Contact_BAO_Contact::checkDomainContact($contactId)) {
    //   return;
    // } else {
    //   CRM_Contact_BAO_Contact::deleteContact($contactId);
    // }
  }

  /**
   * Method to return the url for additional form processing for action
   * and return false if none is needed
   *
   * @param int $ruleActionId
   * @return bool
   * @access public
   */
  public function getExtraDataInputUrl($ruleActionId) {
    return FALSE;
  }

}
