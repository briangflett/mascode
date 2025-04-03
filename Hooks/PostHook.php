<?php

namespace Civi\Mascode\Hooks;

use Civi\Api4\Contact;
use Civi\Api4\Mailing;
use CRM_Civirules_BAO_Rule;
use Civi\mascode\CiviRules\Trigger\MailingUnsubscribe;

/**
 * Handles post-create events.
 */
class PostHook
{
  public static function handle(string $op, string $objectName, int $objectId, $objectRef)
  {
    if ($op === 'create' && $objectName === 'MailingEvent/Unsubscribe') {
      $contactId = $objectRef->contact_id ?? null;
      $mailingId = $objectRef->mailing_id ?? null;

      if (!$contactId || !$mailingId) {
        \Civi::log()->warning('Missing contact_id or mailing_id in unsubscribe event.');
        return;
      }

      try {
        // Load mailing subject for context (optional)
        $mailing = Mailing::get()
          ->addSelect('subject')
          ->addWhere('id', '=', $mailingId)
          ->execute()
          ->first();

        // Trigger the CiviRules rule
        $trigger = new MailingUnsubscribe();
        $trigger->triggerRuleEvaluation($contactId, [
          'contact_id' => $contactId,
          'mailing_id' => $mailingId,
          'mailing_subject' => $mailing['subject'] ?? '(Unknown)',
        ]);
      } catch (\Exception $e) {
        \Civi::log()->error('Error in PostHook::handle() unsubscribe logic: ' . $e->getMessage());
      }
    }
  }
}
