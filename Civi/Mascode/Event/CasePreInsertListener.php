<?php

/**
 * I am using hooks for now.  May refactor to use Symphony Events later.
 */

// CasePreInsertListener.php
namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\GenericHookEvent;

class CasePreInsertListener implements EventSubscriberInterface {
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_preInsert' => 'onPreInsert',
    ];
  }

  public function onPreInsert(GenericHookEvent $event) {
    if ($event->getEntity() === 'Case') {
      // your logic here
    }
  }
}
