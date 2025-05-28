<?php

// file: Civi/Mascode/Hook/PostInstallOrUpgradeHook.php

/**
 * Install mascode settings.
 * Install CiviRules triggers, actions, & conditions.
 * Apply patches to CiviCRM core.
 */

namespace Civi\Mascode\Hook;

use Civi\Mascode\Patches\PatchManager;

class PostInstallOrUpgradeHook
{
    public static function handle(): void
    {
        self::createMascodeSettings();
        \CRM_Civirules_Utils_Upgrader::insertTriggersFromJson('../CiviRules/triggers.json');
        \CRM_Civirules_Utils_Upgrader::insertActionsFromJson('../CiviRules/actions.json');
        \CRM_Civirules_Utils_Upgrader::insertConditionsFromJson('../CiviRules/conditions.json');

      // Apply patches
        self::applyPatches();
    }

  /**
   * Create settings for code generation
   */
    private static function createMascodeSettings(): void
    {
      // Set admin contact for ServiceRequestToProject action only if setting doesn't exist
        if (
            !\Civi::settings()->getExist('mascode_admin_contact_id') ||
            \Civi::settings()->get('mascode_admin_contact_id') === null
        ) {
            $adminContact = \Civi\Api4\Contact::get(false)
            ->addSelect('id')
            ->addWhere('contact_sub_type', '=', 'MAS_Rep')
            ->addWhere('email_primary.email', '=', 'info@masadvise.org')
            ->execute()
            ->first();

            $adminId = $adminContact['id'] ?? null;
            if ($adminId) {
                \Civi::settings()->set('mascode_admin_contact_id', $adminId);
            }
        }
      // Don't initialize mascode_last_project or mascode_last_service_request
      // CodeGenerator::generate() will create them if they don't exist
    }

  /**
   * Apply patches to CiviCRM core
   */
    private static function applyPatches(): void
    {
        $results = PatchManager::applyAll();

        foreach ($results as $patchName => $result) {
            $status = $result['success'] ? 'SUCCESS' : 'FAILED';
            $message = $result['message'] ?? '';
            \Civi::log()->info("Patch {$patchName}: {$status} - {$message}");
        }
    }
}
