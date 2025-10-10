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

    // Workaround for Smarty template path issue with afform extension
    // Ensure afform/core templates are available to prevent
    // "Unable to load template 'file:afform/customGroups/afblock.tpl'" errors
    $smarty = \CRM_Core_Smarty::singleton();
    $afformCorePath = \Civi::paths()->getPath('[civicrm.root]/ext/afform/core/templates/');
    $templateDirs = $smarty->getTemplateDir();

    // Only add if not already present
    if (!in_array($afformCorePath, $templateDirs)) {
        $smarty->addTemplateDir($afformCorePath);
    }
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
 * Implements hook_civicrm_navigationMenu().
 *
 * Adds Move Cases menu item under Cases.
 */
function mascode_civicrm_navigationMenu(&$menu)
{
    // Find the Cases menu
    $casesMenuId = null;
    foreach ($menu as $id => $item) {
        if (isset($item['attributes']['name']) && $item['attributes']['name'] === 'Cases') {
            $casesMenuId = $id;
            break;
        }
    }

    if ($casesMenuId) {
        // Add our menu item to Cases
        $menu[$casesMenuId]['child'][] = [
            'attributes' => [
                'label' => ts('Move Cases Between Organizations'),
                'name' => 'move_cases_between_orgs',
                'url' => 'civicrm/case/mas-move-cases?reset=1',
                'permission' => 'access CiviCase',
                'operator' => 'AND',
                'separator' => 0,
                'active' => 1,
            ],
        ];
    }
}

// Override envent info page to add custom css for event registration
function mascode_civicrm_pageRun(&$page)
{
    if (get_class($page) == 'CRM_Event_Page_EventInfo') {
        CRM_Core_Resources::singleton()
          ->addStyleFile('mascode', 'css/event-registration.css');
    }
}

/**
 * Implements hook_civicrm_aclWhereClause().
 *
 * Add relationship-based ACL access for users in the Volunteer Consultant ACL role.
 * This allows them to see contacts they have active relationships with.
 */
function mascode_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where)
{
    if (!$contactID) {
        return;
    }

    // Check if the user is in the Volunteer Consultant ACL role (ID 3)
    // by checking if they're in the VC ACL Group (ID 45)
    $inVCGroup = \Civi\Api4\GroupContact::get(FALSE)
        ->addWhere('contact_id', '=', $contactID)
        ->addWhere('group_id', '=', 45)
        ->addWhere('status', '=', 'Added')
        ->execute()
        ->count();

    if (!$inVCGroup) {
        return;
    }

    // Get all contacts this user has active relationships with
    $relatedContacts = \Civi\Api4\Relationship::get(FALSE)
        ->addSelect('contact_id_a', 'contact_id_b')
        ->addWhere('is_active', '=', TRUE)
        ->addClause('OR',
            ['contact_id_a', '=', $contactID],
            ['contact_id_b', '=', $contactID]
        )
        ->execute();

    $allowedContactIds = [];
    foreach ($relatedContacts as $relationship) {
        // Add both sides of the relationship, excluding the current user
        if ($relationship['contact_id_a'] != $contactID) {
            $allowedContactIds[] = $relationship['contact_id_a'];
        }
        if ($relationship['contact_id_b'] != $contactID) {
            $allowedContactIds[] = $relationship['contact_id_b'];
        }
    }

    if (!empty($allowedContactIds)) {
        $contactIdList = implode(',', array_unique($allowedContactIds));
        $where = "( $where OR contact_a.id IN ($contactIdList) )";
    }
}
