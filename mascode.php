<?php

require_once 'mascode.civix.php';

// Load Composer autoload if present
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
}

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
 * Implements hook_civicrm_caseSummary() - display the case summary page.
 */
function mascode_civicrm_caseSummary($caseId)
{
  return \Civi\Mascode\Hooks\CaseSummaryHook::handle($caseId);
}

/**
 * Implements hook_civicrm_pre() - executed prior to saving to the DB.
 */
function mascode_civicrm_pre($op, $objectName, $id, &$params)
{
  \Civi\Mascode\Hooks\PreHook::handle($op, $objectName, $id, $params);
}
