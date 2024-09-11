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
