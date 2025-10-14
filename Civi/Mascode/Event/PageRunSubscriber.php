<?php

// File: Civi/Mascode/Event/PageRunSubscriber.php

namespace Civi\Mascode\Event;

use Civi\Core\Service\AutoSubscriber;
use Civi\Core\Event\GenericHookEvent;

class PageRunSubscriber extends AutoSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_pageRun' => 'onPageRun',
        ];
    }

    /**
     * Override event info page to add custom css for event registration
     *
     * @param \Civi\Core\Event\GenericHookEvent $event
     */
    public function onPageRun(GenericHookEvent $event): void
    {
        $page = $event->page;

        if (get_class($page) == 'CRM_Event_Page_EventInfo') {
            \CRM_Core_Resources::singleton()
                ->addStyleFile('mascode', 'css/event-registration.css');
        }
    }
}
