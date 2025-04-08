<?php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\PostEvent;
use Civi;

class ContactPostSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_post' => 'onPost',
        ];
    }

    public function onPost(PostEvent $event): void
    {
        if ($event->getEntity() !== 'Contact' || $event->getAction() !== 'create') {
            return;
        }

        $contactId = $event->getId();
        $contact = $event->getObject();

        // Do something with the contact if needed
        Civi::log()->info("mascode: Contact created in PostHook: ID = {$contactId}");
    }
}
