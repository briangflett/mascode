<?php

// File: Civi/Mascode/Event/AclGroupSubscriber.php

namespace Civi\Mascode\Event;

use Civi\Core\Service\AutoSubscriber;
use Civi\Core\Event\GenericHookEvent;

class AclGroupSubscriber extends AutoSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_aclGroup' => 'onAclGroup',
        ];
    }

    /**
     * Dynamically populate the "Clients_Assigned_to_Current_VC" smart group
     * to only include contacts where the current user is a Case Coordinator.
     *
     * This hook is called when CiviCRM evaluates smart groups for ACL purposes.
     * The group ID is automatically determined by name lookup, so this code
     * works identically in both development and production environments.
     *
     * @param \Civi\Core\Event\GenericHookEvent $event
     */
    public function onAclGroup(GenericHookEvent $event): void
    {
        // TODO: Implement ACL group logic
        //
        // Access parameters via:
        //   $type = $event->type
        //   $contactID = $event->contactID
        //   $tableName = $event->tableName
        //   $allGroups = $event->allGroups
        //   $currentGroups = $event->currentGroups
        //
        // Implementation should:
        // 1. Look up the "Clients of Current VC" group ID by name
        // 2. Check if this group is in the allGroups
        // 3. Skip if called with legacy table name ('civicrm_saved_search' or 'civicrm_group')
        // 4. Get all contacts where the current user is the Case Coordinator using RelationshipCache
        // 5. Insert those contact IDs into the temporary table ($tableName)
        // 6. Add the group ID to $currentGroups array (modify by reference)
    }
}
