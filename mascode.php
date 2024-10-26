<?php

require_once 'mascode.civix.php';

use CRM_Mascode_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function mascode_civicrm_config(&$config): void
{
  _mascode_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function mascode_civicrm_install(): void
{
  _mascode_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function mascode_civicrm_enable(): void
{
  _mascode_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_buildForm().  --  Commented out for now
 */
// function mascode_civicrm_buildForm($formName, &$form)
// {
//   $overrides = [
//     'CRM_Contact_Form_Contact',
//   ];

//   if (in_array($formName, $overrides)) {
//     $newName = preg_replace('/^CRM_/', 'CRM_Mascode_', $formName);
//     $newName::buildForm($form);
//   }
// }
