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
 * Implements hook_civicrm_caseSummary().
 */
function mascode_civicrm_caseSummary($caseID)
{
  if (empty($caseID)) {
    return;
  }

  // Check if user has permission to view cases
  if (!CRM_Core_Permission::check('view cases')) {
    return; // Prevent unauthorized access
  }

  // Get Case End Date
  $query = "SELECT end_date FROM civicrm_case WHERE id = %1";
  $params = [1 => [$caseID, 'Integer']];
  $endDate = CRM_Core_DAO::singleValueQuery($query, $params);

  // Format and add to summary
  if ($endDate) {
    $formattedDate = CRM_Utils_Date::customFormat($endDate);
    //    $summary['Case End Date'] = $formattedDate;
    
    return array(
      'end_date' => array(
        'label' => ts('Case End Date:'),
        'value' => $formattedDate,
      ),
    );
  }
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
