<?php

/**
 * I am using Symfony EventDispatcher for the hooks that support it.
 * config, install, and enable happen before the container is built, so I need to use the traditional hooks.
 * caseSummary is expecting a return value, so I need to use the traditional hook.
 */

require_once 'mascode.civix.php';

// CiviCRM autoloads via the classloader section in info.xml

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Civi\Mascode\CompilerPass;

/**
 * Implements hook_civicrm_container() - used to register services via service.yml.
 */
function mascode_civicrm_container(ContainerBuilder $container)
{
    // AfformSubmitSubscriber is now auto-registered via scan-classes mixin and AutoSubscriber

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
}

/**
 * Implements hook_civicrm_postInstall().
 */
function mascode_civicrm_postInstall()
{
    \Civi\Mascode\Hook\PostInstallOrUpgradeHook::handle();
}

/**
 * Implements hook_civicrm_postUpgrade().
 */
function mascode_civicrm_postUpgrade($op, $queue)
{
    if ($op == 'check') {
        return true;
    } elseif ($op == 'finish') {
        \Civi\Mascode\Hook\PostInstallOrUpgradeHook::handle();
    }
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

/**
 * Implements hook_civicrm_pre_case_merge().
 * 
 * Preserves MAS case codes before cases are merged during contact merge.
 */
function mascode_civicrm_pre_case_merge($mainContactId, $mainCaseId, $otherContactId, $otherCaseId, $changeClient)
{
    // Only preserve codes if we have a valid case ID
    if ($otherCaseId !== null) {
        \Civi\Mascode\Hook\CaseMergeHook::preserveCaseCodes($otherCaseId);
    }
}
