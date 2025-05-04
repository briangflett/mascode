<?php

/**
 * I am using Symfony EventDispatcher for the hooks that support it.
 * config, install, and enable happen before the container is built, so I need to use the traditional hooks.
 * caseSummary is expecting a return value, so I need to use the traditional hook.
 */
require_once 'mascode.civix.php';

// Load Composer autoload if it exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
}

use CRM_Mascode_ExtensionUtil as E;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Civi\Mascode\CompilerPass;

/**
 * Implements hook_civicrm_container() - used to register services via service.yml.
 */
function mascode_civicrm_container(ContainerBuilder $container)
{
  // This can be removed once we move service definitions to YAML.
  if (function_exists('xdebug_break')) {
    xdebug_break();
  }
  // Register CaseEventListener
 $container->register('mascode.case_event_listener', \Civi\Mascode\Event\CaseEventListener::class)
    ->setPublic(true)
    ->addTag('event_subscriber');

  // Register CaseAccessValidator
  $container->register('mascode.case_access_validator', \Civi\Mascode\Event\CaseAccessValidator::class)
    ->setPublic(true)
    ->addTag('event_subscriber');

  // Register FormPrefillSubscriber
  $container->register('mascode.form_prefill_subscriber', \Civi\Mascode\Event\FormPrefillSubscriber::class)
    ->setPublic(true)
    ->addTag('event_subscriber');
  
  // other services like form actions may need to wait until the container is built
  $container->addCompilerPass(new CompilerPass());

  // I don't need to define CiviRule actions as services, 
  // as those methods are called directly by CiviRules based on rows in the CiviRules tables.
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

// Need to handle caseSummary as a traditional hook for now as it is expecting a return value
// 
function mascode_civicrm_caseSummary($caseId)
{
  return \Civi\Mascode\Hook\CaseSummaryHook::handle($caseId);
}
