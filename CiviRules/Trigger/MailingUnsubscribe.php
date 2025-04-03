<?php

namespace Civi\Mascode\CiviRules\Trigger;

/**
 * @method void triggerRuleEvaluation(int $contactId, array $context = [])
 */
class MailingUnsubscribe extends \CRM_Civirules_Trigger
{

    public function getLabel(): string
    {
        return ts('Mailing: Contact unsubscribed');
    }

    public function getEntity(): string
    {
        return 'MailingEvent/Unsubscribe';
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
        $entity = $this->triggerData;

        $contactId = $entity['contact_id'] ?? null;
        if ($contactId) {
            $this->triggerRuleEvaluation($contactId, $entity);
        }
    }
}
