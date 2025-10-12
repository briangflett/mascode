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

// Override envent info page to add custom css for event registration
function mascode_civicrm_pageRun(&$page)
{
    if (get_class($page) == 'CRM_Event_Page_EventInfo') {
        CRM_Core_Resources::singleton()
          ->addStyleFile('mascode', 'css/event-registration.css');
    }
}

/**
 * Implements hook_civicrm_aclGroup().
 *
 * Dynamically populate the "Clients_Assigned_to_Current_VC" smart group
 * to only include contacts where the current user is a Case Coordinator.
 *
 * This hook is called when CiviCRM evaluates smart groups for ACL purposes.
 * The group ID is automatically determined by name lookup, so this code
 * works identically in both development and production environments.
 *
 * @param string $type The type of permission being checked
 * @param int $contactID The contact ID of the logged-in user
 * @param string $tableName The temporary table name to populate
 * @param array $allGroups All group IDs that the user might have access to
 * @param array $currentGroups Currently populated group IDs
 */
function mascode_civicrm_aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups)
{
    if (!$contactID) {
        return;
    }

    // Look up the "Clients_Assigned_to_Current_VC" group ID by name
    // This allows the same code to work in both dev and production
    static $vcAssignedGroupId = null;

    if ($vcAssignedGroupId === null) {
        try {
            $group = \Civi\Api4\Group::get(false)
                ->addSelect('id')
                ->addWhere('name', '=', 'Clients_Assigned_to_Current_VC')
                ->execute()
                ->first();

            $vcAssignedGroupId = $group['id'] ?? false;
        } catch (Exception $e) {
            // Group doesn't exist yet, disable this hook
            $vcAssignedGroupId = false;
            \Civi::log()->warning('mascode ACL hook: Could not find group "Clients_Assigned_to_Current_VC"');
        }
    }

    // Skip if group doesn't exist
    if (!$vcAssignedGroupId) {
        return;
    }

    // Only process if this group is in the allGroups
    if (!in_array($vcAssignedGroupId, $allGroups)) {
        return;
    }

    // Get all contacts where the current user is the Case Coordinator
    // This uses the RelationshipCache table for performance
    $query = "
        INSERT INTO {$tableName} (contact_id)
        SELECT DISTINCT rc.far_contact_id
        FROM civicrm_relationship_cache rc
        INNER JOIN civicrm_case c ON rc.case_id = c.id
        WHERE rc.near_contact_id = %1
          AND rc.near_relation = 'Case Coordinator is'
          AND rc.is_active = 1
          AND c.is_deleted = 0
    ";

    \CRM_Core_DAO::executeQuery($query, [
        1 => [$contactID, 'Integer']
    ]);

    // Add this group to currentGroups to indicate we've processed it
    $currentGroups[] = $vcAssignedGroupId;
}
