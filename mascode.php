<?php

require_once 'mascode.civix.php';

use CRM_Mascode_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function mascode_civicrm_config(&$config): void {
  _mascode_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function mascode_civicrm_install(): void {
  _mascode_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function mascode_civicrm_enable(): void {
  _mascode_civix_civicrm_enable();
}

/**
 * Adds custom action CreateProject to the CiviRules Action Collection
 *
 */
function mascode_civicrm_civirulesAction(CRM_Civirules_ActionCollection $actionCollection) {
    $actionCollection->add('mascode_custom_action', ts('Create Case'), 'CRM_Mascode_Action_CustomAction');
}

/**
 * Adds custom action CreateProject to the CiviRules Action Collection
 *
 */
function masdemo_civicrm_config(&$config) {
    _masdemo_civix_civicrm_config($config);

    // Register the path
    CRM_Utils_System::registerRoute(
        'masdemo/extern/myscript',
        ['path' => 'civicrm/extern/masdemo/myscript', 'callback' => 'masdemo_myscript_callback']
    );
}

function masdemo_myscript_callback() {
    // Your script logic goes here
}
