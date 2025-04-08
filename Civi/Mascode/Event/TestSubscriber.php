<?php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\ConfigEvent;
use Civi;

class TestSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_config' => 'onConfig',
        ];
    }

    public function onConfig(ConfigEvent $event): void
    {
        error_log("✅ TestSubscriber: hook_civicrm_config fired");
        Civi::log()->error("mascode: TestSubscriber: hook_civicrm_config fired");
        xdebug_break();
    }
}
