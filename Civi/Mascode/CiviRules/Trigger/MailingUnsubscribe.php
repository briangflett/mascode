<?php

namespace Civi\Mascode\CiviRules\Trigger;

if (!class_exists('\CRM_Civirules_Trigger_Post')) {
    // \Civi::log()->warning("MailingUnsubscribe loaded before CRM_Civirules_Trigger_Post is available!");
} else {
    // \Civi::log()->info("Parent class CRM_Civirules_Trigger_Post is ready.");
}

// Workaround to ensure base class is loaded before this class is parsed.
if (!class_exists('CRM_Civirules_Trigger_Post')) {
    require_once 'CRM/Civirules/Trigger/Post.php'; // Relative to CiviCRM base
    // \Civi::log()->warning("I have manually required it");
}


/**
 * @method void triggerRuleEvaluation(int $contactId, array $context = [])
 */
class MailingUnsubscribe extends \CRM_Civirules_Trigger_Post
{
    public function debugParentCheck()
    {
        return get_parent_class($this);
    }

    public function getLabel(): string
    {
        return ts('Mailing: Contact unsubscribed');
    }

    public function getEntity(): string
    {
        return 'SubscriptionHistory';
    }

    public function getEvents(): array
    {
        return ['mailing_unsubscribe'];
    }

    public function getSummary(): string
    {
        return ts('Triggered when a contact unsubscribes from a mailing.');
    }

    protected function reactOnEntity()
    {
        if (!isset($this->triggerData) || empty($this->triggerData['contact_id'])) {
            return;
        }
        xdebug_break();
        $entity = $this->triggerData;

        $contactId = $entity['contact_id'] ?? null;
        if ($contactId) {
            $this->triggerRuleEvaluation($contactId, $entity);
        }
    }
}
