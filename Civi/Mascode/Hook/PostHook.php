<?php

namespace Civi\Mascode\Hook;

use Civi\Api4\Mailing;
use Civi\mascode\CiviRules\Trigger\MailingUnsubscribe;

/**
 * Handles post-create events.
 */
class PostHook
{
  public static function handle(string $op, string $objectName, int $objectId, $objectRef)
  {
    // \Civi::log()->info("hook_civicrm_post fired", [
    //   'op' => $op,
    //   'objectName' => $objectName,
    //   'objectId' => $objectId,
    //   'objectRef' => $objectRef,
    // ]);

    if ($op === 'create' && $objectName === 'SubscriptionHistory') {
      /** @var \CRM_Contact_BAO_SubscriptionHistory $objectRef */
      if (
        $objectRef->status === 'Removed' &&
        $objectRef->method === 'Email'
      ) {
        // \Civi::log()->info("Unsub triggered for contact {$objectRef->contact_id} via group {$objectRef->group_id}");
        
        $trigger = new \Civi\Mascode\CiviRules\Trigger\MailingUnsubscribe();

        // \Civi::log()->info("Calling triggerRuleEvaluation on class: " . get_class($trigger));
        // \Civi::log()->info("Available methods: " . print_r(get_class_methods($trigger), true));

        $trigger->triggerRuleEvaluation($objectRef->contact_id, [
          'contact_id' => $objectRef->contact_id,
          'group_id' => $objectRef->group_id,
          'status' => $objectRef->status,
          'method' => $objectRef->method,
        ]);
      }
    }

    // if ($op === 'create' && $objectName === 'MailingEvent/Unsubscribe') {
    //   $contactId = $objectRef->contact_id ?? null;
    //   $mailingId = $objectRef->mailing_id ?? null;

    //   if (!$contactId || !$mailingId) {
    //     \Civi::log()->warning('Missing contact_id or mailing_id in unsubscribe event.');
    //     return;
    //   }

    //   try {
    //     // Load mailing subject for context (optional)
    //     $mailing = Mailing::get()
    //       ->addSelect('subject')
    //       ->addWhere('id', '=', $mailingId)
    //       ->execute()
    //       ->first();

    //     xdebug_break();
    //     // Trigger the CiviRules rule
    //     $trigger = new MailingUnsubscribe();
    //     $trigger->triggerRuleEvaluation($contactId, [
    //       'contact_id' => $contactId,
    //       'mailing_id' => $mailingId,
    //       'mailing_subject' => $mailing['subject'] ?? '(Unknown)',
    //     ]);
    //   } catch (\Exception $e) {
    //     \Civi::log()->error('Error in PostHook::handle() unsubscribe logic: ' . $e->getMessage());
    //   }
    // }
  }
}
