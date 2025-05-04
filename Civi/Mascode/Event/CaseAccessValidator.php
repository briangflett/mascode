<?php

namespace Civi\Mascode\Event;

use Civi\FormProcessor\Event\PreFormProcessorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Afform\Utils\AfformAuth;

class CaseAccessValidator implements EventSubscriberInterface {

  public function __construct() {
    \Civi::log()->debug('🔍 CaseAccessValidator instantiated');
  }

  public static function getSubscribedEvents(): array {
    return [
      PreFormProcessorEvent::class => ['onPreFormProcessor', 100],
    ];
  }

  public function onPreFormProcessor(PreFormProcessorEvent $event): void {
    $contactId = AfformAuth::getContactId();
    $params = $event->getParameters();
    $caseId = (int) ($params['Case1.id'] ?? $_GET['case_id'] ?? 0); // Case1.id is common for auto-named entity blocks

    if (!$contactId || !$caseId) {
      throw new \CRM_Core_Exception("Missing or invalid contact or case.");
    }

    // Lookup the primary contact of the case
    $orgId = \Civi\Api4\CaseContact::get()
      ->addSelect('contact_id')
      ->addWhere('case_id', '=', $caseId)
      ->addWhere('is_primary', '=', 1)
      ->execute()
      ->first()['contact_id'] ?? null;

    if (!$orgId) {
      throw new \CRM_Core_Exception("Case not found or not linked to an organization.");
    }

    // Check if authenticated contact is employee of that organization
    $relationship = \Civi\Api4\Relationship::get()
      ->addSelect('id')
      ->addWhere('contact_id_a', '=', $contactId)
      ->addWhere('contact_id_b', '=', $orgId)
      ->addWhere('relationship_type_id.label_a_b', '=', 'Employee of')
      ->addWhere('is_active', '=', 1)
      ->execute()
      ->first();

    if (!$relationship) {
      throw new \CRM_Core_Exception("You are not authorized to view this case.");
    }
  }
}
