<?php

/**
 * I am trying to follow the latest object oriented best practices for this extension, 
 * but I have been running into lots of issues trying to convert from traditional hooks to Symfony Events.  
 * 
 * I couldn't get the subscribers registered declaratively through services.yml
 * I couldn't get the subscribers registered imperitavely through addSubscriber()
 * So I am currently adding them as listeners imperitively using addListener()
 * Perhaps I should just go back to using hooks??? 
 */

require_once 'mascode.civix.php';

// Load Composer autoload if present
// if (file_exists(__DIR__ . '/vendor/autoload.php')) {
//   require_once __DIR__ . '/vendor/autoload.php';
// }

use CRM_Mascode_ExtensionUtil as E;

// use Civi\Mascode\Event\CasePreListener;
// use Civi\Mascode\Event\CasePostListener;
// use Civi\Mascode\Event\CaseSummaryListener;

/**
 * Implement hook_civicrm_caseSummary() inline for now and comment everything else out.
 */
function mascode_civicrm_caseSummary($caseId)
{
  if (empty($caseId)) {
    return;
  };

  try {
      $case = \Civi\Api4\CiviCase::get()
          ->addWhere('id', '=', $caseId)
          ->setLimit(1)
          ->execute()
          ->first();

      if (!$case) {
          return [];
      }
    } catch (\Exception $e) {
        return [];
    }

    $formattedEndDate = !empty($case['end_date'])
        ? CRM_Utils_Date::customFormat($case['end_date'])
        : '';

    //   Instead of returning the array of values and styling trough CSS
    //   return the HTML itself so you can reference the civi styling classes
    //   \CRM_Core_Resources::singleton()->addStyleFile('mascode', 'css/extras.css');

    $html = '<table class="report crm-entity case-summary" style="margin-top: 1em;"><tbody><tr>';
    $html .= '<td class="label"><span class="crm-case-summary-label">End Date:</span>&nbsp;' . $formattedEndDate . '</td>';
    $html .= '</tr></tbody></table>';
    return [
        [
            'label' => '',
            'value' => $html,
        ],
    ];
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function mascode_civicrm_config(&$config)
{
  _mascode_civix_civicrm_config($config);
  
  // if (isset(\Civi::$statics[__FUNCTION__])) return;
  // \Civi::$statics[__FUNCTION__] = 1;

  // $dispatcher = \Civi::dispatcher();

  // $dispatcher->addListener('hook_civicrm_pre', [new CasePreListener(), 'onPre']);
  // $dispatcher->addListener('hook_civicrm_post', [new CasePostListener(), 'onPost']);
  // $dispatcher->addListener('hook_civicrm_caseSummary', [new CaseSummaryListener(), 'onCaseSummary']);
  // xdebug_break();
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function mascode_civicrm_install(): void
{
  _mascode_civix_civicrm_install();

  // Delegate custom installation code to my OOP class
  // \Civi\Mascode\Hook\InstallHook::handle();
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
 * Implements hook_civicrm_triggerInfo() - required to create CiviRules triggers.
 */
// function mascode_civicrm_triggerInfo(&$triggerInfo)
// {
//   $triggerInfo[] = new \Civi\Mascode\CiviRules\Trigger\MailingUnsubscribe();
// }

// /**
//  * The rest of this file is not required.  
//  * These are old hooks that have beeen replaced with event subscribers.
//  */

// /**
//  * Implements hook_civicrm_caseSummary() - display the case summary page.
//  */
// function mascode_civicrm_caseSummary($caseId)
// {
//   return \Civi\Mascode\Hook\CaseSummaryHook::handle($caseId);
// }

// /**
//  * Implements hook_civicrm_pre() - executed prior to saving to the DB.
//  */
// function mascode_civicrm_pre($op, $objectName, $id, &$params)
// {
//   \Civi\Mascode\Hook\PreHook::handle($op, $objectName, $id, $params);
// }

// /**
//  * Implements hook_civicrm_post() - executed after to saving to the DB.
//  */
// function mascode_civicrm_post(string $op, string $objectName, int $objectId, &$objectRef)
// {
//   \Civi\Mascode\Hooks\PostHook::handle($op, $objectName, $objectId, $objectRef);
// }

// /**
//  * Implements hook_civicrm_buildForm() - executed prior to saving to the DB.
//  */
// function mascode_civicrm_buildForm($formName, &$form)
// {
//   \Civi\Mascode\Hook\BuildFormHook::handle($formName, $form);
// }

// /**
//  * Example - So far all my hook handlers are stateless.
//  * If I need a hook handler with state, I should use a hook dispatcher to avoid repeated instantiation
//  */
// //   \Civi\Mascode\Utils\HookDispatcher::call(\Civi\Mascode\Hooks\StatefulHook::class, 'handle', $event);