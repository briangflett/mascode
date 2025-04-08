<?php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\GenericHookEvent;
use CRM_Core_Resources;

class BuildFormSubscriber implements EventSubscriberInterface
{

  public static function getSubscribedEvents(): array
  {
    return [
      'hook_civicrm_buildForm' => 'onBuildForm',
    ];
  }

  public function onBuildForm(GenericHookEvent $event): void
  {
    xdebug_break();
    $formName = $event->getArg(0);
    $form = &$event->getArg(1);

    if ($formName === 'CRM_Contact_Form_Edit_Organization') {
      CRM_Core_Resources::singleton()->addScript("
        cj(function($) {
          $('form').on('submit', function() {
            var urlField = $('input[name=\"url\"]');
            var url = urlField.val().trim();
            if (url && !/^https?:\\/\\//i.test(url)) {
              urlField.val('http://' + url);
            }
          });
        });
      ");
    }
  }
}
