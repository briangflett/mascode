<?php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\PostEvent;
use Civi;

class ContactCreatedSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_post' => ['onPost', 0],
        ];
    }

    public function onPost(PostEvent $event): void
    {
        if ($event->entity === 'Contact' && $event->action === 'create') {
            $contactId = $event->id;
            Civi::log()->info("mascode: Contact created with ID {$contactId}");
        }
    }
}
