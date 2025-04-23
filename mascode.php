<?php

/**
 * I am using Symfony for Form Processor Actions and CiviRules Actions.
 * I am using hooks for listeners for now.  I may refactor to use Symphony Events later.
 */

require_once 'mascode.civix.php';

// Load Composer autoload if it exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
}

use CRM_Mascode_ExtensionUtil as E;
use Civi\Mascode\CompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements hook_civicrm_container() - used to register services via service.yml.
 */
function mascode_civicrm_container(ContainerBuilder $container)
{
  $container->addCompilerPass(new CompilerPass());
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function mascode_civicrm_config(&$config)
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

  \Civi\Mascode\Hook\InstallHook::handle();
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
 * Implement hook_civicrm_caseSummary().
 */
function mascode_civicrm_caseSummary($caseId)
{
  return \Civi\Mascode\Hook\CaseSummaryHook::handle($caseId);
}

// /**
//  * Implements hook_civicrm_pre() - executed prior to saving to the DB.
//  */
function mascode_civicrm_pre($op, $objectName, $id, &$params)
{
  \Civi\Mascode\Hook\PreHook::handle($op, $objectName, $id, $params);
}

// /**
//  * Implements hook_civicrm_post() - executed after to saving to the DB.
//  */
function mascode_civicrm_post(string $op, string $objectName, int $objectId, &$objectRef)
{
  \Civi\Mascode\Hook\PostHook::handle($op, $objectName, $objectId, $objectRef);
}

// /**
//  * Example - So far all my hook handlers are stateless.
//  * If I need a hook handler with state, I should use a hook dispatcher to avoid repeated instantiation
//  */
// //   \Civi\Mascode\Utils\HookDispatcher::call(\Civi\Mascode\Hooks\StatefulHook::class, 'handle', $event);