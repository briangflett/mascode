<?php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\GenericHookEvent;

class ContactPreSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_pre' => 'onPre',
        ];
    }

    public function __construct()
    {
        error_log("⚡ Loaded ContactPreSubscriber from " . __FILE__);
    }

    public function onPre(GenericHookEvent $event): void
    {
        error_log('🧪 Received event of class: ' . get_class($event));

        if (method_exists($event, 'getEntity') && method_exists($event, 'getAction')) {
            error_log('✅ Event has getEntity and getAction methods');
            if ($event->getEntity() === 'Contact' && in_array($event->getAction(), ['create', 'edit'])) {
                $params = &$event->getParams();
                if (!empty($params['url']) && !preg_match('#^https?://#i', $params['url'])) {
                    $params['url'] = 'http://' . $params['url'];
                }
            }
        } else {
            error_log('❌ Event is missing getEntity/getAction');
        }

        if ($event->getEntity() === 'Contact' && in_array($event->getAction(), ['create', 'edit'])) {
            $params = &$event->getParams();
            if (!empty($params['url']) && !preg_match('#^https?://#i', $params['url'])) {
                $params['url'] = 'http://' . $params['url'];
            }
        }
    }
}
